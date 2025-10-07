<?php

namespace App\Http\Controllers;
use App\Models\Booking; // legacy (masih ada untuk kompatibilitas sementara)
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use App\Models\Pelanggan;
use App\Models\Kamar;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


class BookingController extends Controller
{
    /**
     * Helper: mapping status key/label/badge
     */
    private function statusMeta(int $status): array
    {
        $keyMap = [1=>'dipesan',2=>'checkin',3=>'checkout',4=>'dibatalkan'];
        $labelMap = [
            'dipesan'=>'Dipesan',
            'checkin'=>'Check-In',
            'checkout'=>'Checkout',
            'dibatalkan'=>'Dibatalkan'
        ];
        $badgeMap = [
            'dipesan'=>'bg-warning text-dark',
            'checkin'=>'bg-info text-dark',
            'checkout'=>'bg-secondary',
            'dibatalkan'=>'bg-dark'
        ];
        $key = $keyMap[$status] ?? 'dipesan';
        return [
            'key'=>$key,
            'label'=>$labelMap[$key] ?? $key,
            'badge'=>$badgeMap[$key] ?? 'bg-secondary'
        ];
    }
    public function index(Request $request)
    {
        // Default context for availability: today only (create form still validates conflicts by selected dates)
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();

        $kamarAll = Kamar::orderBy('tipe')->orderBy('nomor_kamar')->get();
        $activeOrders = BookingOrder::with(['pelanggan','items.kamar'])
            ->whereIn('status',[1,2])
            ->where(function($q) use ($todayStart,$todayEnd){
                $q->whereBetween('tanggal_checkin', [$todayStart,$todayEnd])
                  ->orWhereBetween('tanggal_checkout', [$todayStart,$todayEnd])
                  ->orWhere(function($qq) use ($todayStart,$todayEnd){
                      $qq->where('tanggal_checkin','<=',$todayStart)
                          ->where('tanggal_checkout','>=',$todayEnd);
                  });
            })
            ->get();
        $occupiedIds = [];
        foreach($activeOrders as $order){
            foreach($order->items as $item){ $occupiedIds[$item->kamar_id] = true; }
        }
        $availableKamar = $kamarAll->filter(fn($k)=> !isset($occupiedIds[$k->id]))->values();

        $pelangganList = Pelanggan::orderBy('nama')->get();

        // Client-side pagination via DataTables in the view
        $orders = BookingOrder::with(['pelanggan','items.kamar'])
            ->orderByDesc('tanggal_checkin')
            ->get();

        return view('booking', [
            'orders'=>$orders,
            'pelangganList'=>$pelangganList,
            'availableKamar'=>$availableKamar,
        ]);
    }

    /**
     * Simpan booking baru (walk-in atau online)
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'pelanggan_id'      => 'required|exists:pelanggan,id',
            'kamar_ids'         => 'required|array|min:1',
            'kamar_ids.*'       => 'exists:kamar,id',
            'tanggal_checkin'   => 'required|date|before:tanggal_checkout',
            'tanggal_checkout'  => 'required|date|after:tanggal_checkin',
            'jumlah_tamu'       => 'required|integer|min:1',
            'pemesanan'         => 'required|in:0,1,2,3',
            // lifecycle status is integer (1..4). Optional for create; we'll set based on pemesanan by default
            'status'            => 'nullable|integer|in:1,2,3,4',
            'payment_status'    => 'nullable|in:dp,lunas',
            'dp_percentage'     => 'nullable|integer|min:0|max:100',
            'catatan'           => 'nullable|string',
            'biaya_tambahan'    => 'nullable|integer|min:0',
        ]);
        if ($validator->fails()) {
            return redirect()->route('booking.index')
                ->withErrors($validator, 'booking_create')
                ->withInput();
        }
        $data = $validator->validated();

        $kamarList = Kamar::whereIn('id',$data['kamar_ids'])->get();
        if($kamarList->count() !== count($data['kamar_ids'])){
            return redirect()->route('booking.index')
                ->withErrors(['kamar_ids' => 'Data kamar tidak valid'], 'booking_create')
                ->withInput();
        }

        // Cek overlap per kamar terhadap order aktif (status 1/2)
        $startCheck = Carbon::parse($data['tanggal_checkin']);
        $endCheck = Carbon::parse($data['tanggal_checkout']);
        // Tambahan waktu: adjust checkout by selected extension (does not change pricing directly)
        $extraSel = $request->get('extra_time','none');
        if ($extraSel === 'h3') { $endCheck = $endCheck->copy()->addHours(3); }
        elseif ($extraSel === 'h6') { $endCheck = $endCheck->copy()->addHours(6); }
        elseif ($extraSel === 'h9') { $endCheck = $endCheck->copy()->addHours(9); }
        elseif ($extraSel === 'd1') { $endCheck = $endCheck->copy()->addDay(); }
        foreach($kamarList as $k){
            $conflict = BookingOrderItem::where('kamar_id',$k->id)
                ->whereHas('order', function($q) use ($startCheck,$endCheck){
                    $q->whereIn('status',[1,2])
                      ->where(function($qq) use ($startCheck,$endCheck){
                          $qq->whereBetween('tanggal_checkin', [$startCheck,$endCheck])
                             ->orWhereBetween('tanggal_checkout', [$startCheck,$endCheck])
                             ->orWhere(function($qx) use ($startCheck,$endCheck){
                                 $qx->where('tanggal_checkin','<=',$startCheck)
                                    ->where('tanggal_checkout','>=',$endCheck);
                             });
                      });
                })
                ->exists();
            if($conflict){
                return redirect()->route('booking.index')
                    ->withErrors(['kamar_ids' => 'Kamar '.$k->nomor_kamar.' sudah dibooking pada rentang waktu tersebut'], 'booking_create')
                    ->withInput();
            }
        }

        $start = $startCheck; $end = $endCheck;
        $days = max($start->diffInDays($end),1);

    // Default lifecycle: walk-in defaults to checkin (2), online defaults to dipesan (1)
    $status = isset($data['status']) ? (int)$data['status'] : ((int)$data['pemesanan'] == 0 ? 2 : 1);

        // Base total from selected rooms * nights
        $baseTotal = 0;
        foreach($kamarList as $k){
            $baseTotal += $days * (int)$k->harga;
        }
        // If booking duration is half-day (<= 6 hours), charge 50% of base day rate
        $diffHours = $endCheck->diffInHours($startCheck);
        if ($diffHours <= 6) {
            $baseTotal = (int) round($baseTotal * 0.5);
        }
        // Do not apply extra_time multipliers to price; only date is adjusted above
        // Per-head mode: if enabled and guests > 2, add 50k per extra guest; ensure min 100k
        $jumlahTamu = (int)($request->get('jumlah_tamu') ?? 1);
        $perHeadMode = (bool)$request->boolean('per_head_mode');
        if($perHeadMode && $jumlahTamu > 2){
            $baseTotal += 50000 * ($jumlahTamu - 2);
        }
        $baseTotal = max($baseTotal, 100000);
        // Discounts: review (10%), follow (10%), sequential if both
        $discReview = $request->boolean('discount_review');
        $discFollow = $request->boolean('discount_follow');
        $totalAfterDisc = $baseTotal;
        if($discReview){ $totalAfterDisc = (int) round($totalAfterDisc * 0.9); }
        if($discFollow){ $totalAfterDisc = (int) round($totalAfterDisc * 0.9); }
        $diskonNominal = max(0, $baseTotal - $totalAfterDisc);
        $totalOrder = $totalAfterDisc;

        // DP nominal and payment status decision
        $dpAmount = (int)($request->get('dp_amount') ?? 0);
        $paymentStatus = $dpAmount >= $totalOrder ? 'lunas' : 'dp';
        $paymentMethod = $request->get('payment_method'); // cash, transfer, qris, card (nullable)
        $biayaTambahan = (int)($request->get('biaya_tambahan') ?? 0);

        $order = BookingOrder::create([
            'pelanggan_id' => $data['pelanggan_id'],
            'tanggal_checkin' => $start,
            'tanggal_checkout' => $end,
            'jumlah_tamu_total' => $data['jumlah_tamu'],
            'status' => $status,
            'pemesanan' => (int)$data['pemesanan'],
            'catatan' => $data['catatan'] ?? null,
            'total_harga' => $totalOrder,
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod ? strtolower($paymentMethod) : null,
            'dp_percentage' => null,
            'dp_amount' => $dpAmount > 0 ? $dpAmount : null,
            'discount_review' => $discReview,
            'discount_follow' => $discFollow,
            // Store as 'none' to avoid enum mismatch; date already extended
            'extra_time' => 'none',
            'per_head_mode' => $perHeadMode,
            'diskon' => $diskonNominal,
            'biaya_tambahan' => $biayaTambahan > 0 ? $biayaTambahan : null,
        ]);

        foreach($kamarList as $k){
            BookingOrderItem::create([
                'booking_order_id' => $order->id,
                'kamar_id' => $k->id,
                'malam' => $days,
                'harga_per_malam' => (int)$k->harga,
                'subtotal' => $days * (int)$k->harga,
            ]);
            // Room status update removed
        }

        // Ledger: DP in
        if($dpAmount > 0){
            DB::table('cash_ledger')->insert([
                'booking_id' => $order->id,
                'type' => 'dp_in',
                'amount' => $dpAmount,
                'note' => 'Uang masuk DP',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('booking.index')
            ->with('success','Booking multi-kamar berhasil dibuat.');
    }

    /**
     * Update status booking (checkin, checkout, cancel)
     */
    public function updateStatus(Request $request, $id)
    {
    // Sekarang status mengacu ke BookingOrder
    $booking = BookingOrder::with('items.kamar')->findOrFail($id);
        $action = $request->get('action');
        $allowed = ['checkin','checkout'];
        if (!in_array($action, $allowed)) {
            return redirect()->back()->with('error', 'Aksi tidak dikenal');
        }
        switch ($action) {
            case 'checkin':
                if ($booking->status != 1) return redirect()->back()->with('error','Tidak dapat check-in');
                if ($booking->payment_status !== 'lunas') return redirect()->back()->with('error','Check-in hanya diizinkan jika pembayaran sudah Lunas');
                $booking->status = 2;
                // Room status update removed
                break;
            case 'checkout':
                if ($booking->status != 2) return redirect()->back()->with('error','Tidak dapat check-out');
                $booking->status = 3;
                // Otomatis set pembayaran menjadi lunas saat checkout
                $booking->payment_status = 'lunas';
                $booking->dp_percentage = 100; // tandai sudah lunas penuh
                // Room status update removed
                break;
        }
        $booking->save();
        if (request()->wantsJson()) {
            $statusKeyMap = [1=>'dipesan',2=>'checkin',3=>'checkout',4=>'dibatalkan'];
            $statusLabelMap = [
                'dipesan' => 'Dipesan',
                'checkin' => 'Check-In',
                'checkout' => 'Checkout',
                'dibatalkan' => 'Dibatalkan'
            ];
            $badgeClassMap = [
                'dipesan' => 'bg-warning text-dark',
                'checkin' => 'bg-info text-dark',
                'checkout' => 'bg-secondary',
                'dibatalkan' => 'bg-dark'
            ];
            $key = $statusKeyMap[$booking->status] ?? 'dipesan';
            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'status_key' => $key,
                    'status_label' => $statusLabelMap[$key] ?? $key,
                    'badge_class' => $badgeClassMap[$key] ?? 'bg-secondary',
                    'status_url' => route('booking.status', $booking->id),
                ]
            ]);
        }
        return redirect()->back()->with('success', 'Status booking diperbarui');
    }

    /**
     * Detail booking order (JSON for modal)
     */
    public function show(Request $request, $id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar'])->findOrFail($id);
        if($request->wantsJson()){
            $meta = $order->status_meta; // new unified meta
            // Riwayat lain pelanggan (maks 10 terbaru) kecuali order ini
            $other = collect();
            if($order->pelanggan_id){
                $other = BookingOrder::where('pelanggan_id',$order->pelanggan_id)
                    ->where('id','!=',$order->id)
                    ->orderByDesc('tanggal_checkin')
                    ->limit(10)
                    ->get()
                    ->map(function($o){
                        $m = [1=>'dipesan',2=>'checkin',3=>'checkout',4=>'dibatalkan'];
                        $label = [
                            'dipesan'=>'Dipesan',
                            'checkin'=>'Check-In',
                            'checkout'=>'Checkout',
                            'dibatalkan'=>'Dibatalkan'
                        ];
                        $key = $m[$o->status] ?? 'dipesan';
                        return [
                            'id'=>$o->id,
                            'tanggal_checkin'=>$o->tanggal_checkin,
                            'tanggal_checkout'=>$o->tanggal_checkout,
                            'status_key'=>$key,
                            'status_label'=>$label[$key] ?? $key,
                            'total_harga'=>$o->total_harga,
                        ];
                    });
            }
            return response()->json([
                'id'=>$order->id,
                'pelanggan'=>[
                    'id'=>$order->pelanggan?->id,
                    'nama'=>$order->pelanggan?->nama,
                    'telepon'=>$order->pelanggan?->telepon,
                ],
                'tanggal_checkin'=>$order->tanggal_checkin,
                'tanggal_checkout'=>$order->tanggal_checkout,
                'jumlah_tamu_total'=>$order->jumlah_tamu_total,
                'status'=>$order->status,
                'status_label'=>$meta['label'] ?? '-',
                'status_meta'=>$meta,
                'dp_percentage'=>$order->dp_percentage,
                'payment_status'=>$order->payment_status,
                'pemesanan'=>$order->pemesanan,
                'catatan'=>$order->catatan,
                'total_harga'=>$order->total_harga,
                'total_cafe'=>$order->total_cafe ?? 0,
                'biaya_tambahan'=>$order->biaya_tambahan ?? 0,
                'grand_total'=>(($order->total_harga) + ($order->total_cafe ?? 0)) - ($order->diskon ?? 0) + ($order->biaya_tambahan ?? 0),
                'total_kamar'=>$order->items->count(),
                'total_malam'=>$order->items->first()?->malam ?? 0,
                'items'=>$order->items->map(function($it){
                    return [
                        'id'=>$it->id,
                        'kamar_id'=>$it->kamar_id,
                        'nomor_kamar'=>$it->kamar?->nomor_kamar,
                        'tipe'=>$it->kamar?->tipe,
                        'malam'=>$it->malam,
                        'harga_per_malam'=>$it->harga_per_malam,
                        'subtotal'=>$it->subtotal,
                    ];
                })->values(),
                'other_orders'=>$other,
            ]);
        }
        return redirect()->route('booking.index')->with('error','Format tidak didukung');
    }

    /**
     * Toggle / update payment status (dp -> lunas).
     */
    public function updatePaymentStatus(Request $request, $id)
    {
        $order = BookingOrder::findOrFail($id);
        $new = $request->get('payment_status');
        if(!in_array($new,['dp','lunas'])){
            return response()->json(['success'=>false,'message'=>'Status pembayaran tidak valid'],422);
        }
        $order->payment_status = $new;
        $order->save();
        if($request->wantsJson()){
            return response()->json(['success'=>true,'payment_status'=>$order->payment_status]);
        }
        return redirect()->back()->with('success','Status pembayaran diperbarui');
    }

    /**
     * Update sederhana booking order: tanggal, pemesanan, catatan (tanpa ubah kamar set)
     */
    public function update(Request $request, $id)
    {
        $order = BookingOrder::with(['items.kamar','cafeOrders'])->findOrFail($id);
        $data = $request->validate([
            'tanggal_checkin'=>'required|date|before:tanggal_checkout',
            'tanggal_checkout'=>'required|date|after:tanggal_checkin',
            'pemesanan'=>'required|in:0,1,2,3',
            'jumlah_tamu_total'=>'nullable|integer|min:1',
            'pelanggan_id'=>'nullable|exists:pelanggan,id',
            'catatan'=>'nullable|string',
            'status'=>'nullable|integer|in:1,2,3,4',
            'payment_status'=>'nullable|in:dp,lunas,dp_cancel',
            'dp_amount'=>'nullable|integer|min:0',
            'discount_review'=>'nullable|boolean',
            'discount_follow'=>'nullable|boolean',
            'extra_time'=>'nullable|in:none,h3,h6,h9,d1',
            'per_head_mode'=>'nullable|boolean',
            'payment_method'=>'nullable|string|max:20',
            'biaya_tambahan'=>'nullable|integer|min:0',
        ]);
        // Optional new fields (won't error if absent from form)
        $dpPct = $request->get('dp_percentage');
        $start = Carbon::parse($data['tanggal_checkin']);
        $end = Carbon::parse($data['tanggal_checkout']);
        // Apply tambahan waktu by extending checkout time only (no price multiplier)
        $extraSel = $data['extra_time'] ?? 'none';
        if ($extraSel === 'h3') { $end = $end->copy()->addHours(3); }
        elseif ($extraSel === 'h6') { $end = $end->copy()->addHours(6); }
        elseif ($extraSel === 'h9') { $end = $end->copy()->addHours(9); }
        elseif ($extraSel === 'd1') { $end = $end->copy()->addDay(); }
        $days = max($start->diffInDays($end),1);

        // Base recalc from item nightly rates
        $base = 0;
        foreach($order->items as $it){
            $it->malam = $days;
            $it->subtotal = $days * (int)$it->harga_per_malam;
            $it->save();
            $base += $it->subtotal;
        }
        // Apply automatic half-day adjustment first if duration <= 6 hours
        $diffHours = $end->diffInHours($start);
        if ($diffHours <= 6) {
            $base = (int) round($base * 0.5);
        }
        // Do not apply extra_time multipliers to price; only date adjusted above
        // Per-head
        $perHead = (bool)($data['per_head_mode'] ?? $order->per_head_mode ?? false);
        $jumlahTamu = (int)($data['jumlah_tamu_total'] ?? $order->jumlah_tamu_total ?? 1);
        if($perHead && $jumlahTamu>2){ $base += 50000 * ($jumlahTamu - 2); }
        $base = max($base, 100000);
        // Discounts
        $discReview = (bool)($data['discount_review'] ?? $order->discount_review ?? false);
        $discFollow = (bool)($data['discount_follow'] ?? $order->discount_follow ?? false);
        $after = $base;
        if($discReview){ $after = (int) round($after * 0.9); }
        if($discFollow){ $after = (int) round($after * 0.9); }
        $diskonNom = max(0, $base - $after);

        // Apply editable fields
        if(isset($data['pelanggan_id'])) $order->pelanggan_id = $data['pelanggan_id'];
        if(isset($data['jumlah_tamu_total'])) $order->jumlah_tamu_total = $data['jumlah_tamu_total'];
        $order->tanggal_checkin = $start;
        $order->tanggal_checkout = $end;
        $order->pemesanan = (int)$data['pemesanan'];
        $order->catatan = $data['catatan'] ?? null;
        $order->total_harga = $after; // room total after modifiers
        $order->discount_review = $discReview;
        $order->discount_follow = $discFollow;
        // Store as 'none' to avoid enum mismatch; checkout already extended
        $order->extra_time = 'none';
        $order->per_head_mode = $perHead;
        $order->diskon = $diskonNom;
        if(isset($data['biaya_tambahan'])){ $order->biaya_tambahan = (int)$data['biaya_tambahan']; }
        // Payment handling
        if(isset($data['dp_amount'])){ $order->dp_amount = (int)$data['dp_amount']; }
        if(isset($data['payment_status'])){ $order->payment_status = $data['payment_status']; }
        if(isset($data['payment_method'])){ $order->payment_method = strtolower($data['payment_method']); }
        // Persist lifecycle status if provided (1..4)
        if(isset($data['status'])){
            $order->status = (int)$data['status'];
        }
        // Jika status menjadi checkout (3), paksa pelunasan
        if(isset($data['status']) && (int)$data['status'] === 3){
            $order->payment_status = 'lunas';
            $order->dp_percentage = 100; // lunas penuh saat checkout
        }
        if($dpPct!==null && $dpPct!==''){ $order->dp_percentage = max(0,min(100,(int)$dpPct)); }
        $order->save();

        // Ledger logic
        // If payment becomes lunas and dp_amount < total, log remaining-in once
        if(($order->payment_status==='lunas') && (int)($order->dp_amount ?? 0) < (int)$order->total_harga){
            $remaining = (int)$order->total_harga - (int)($order->dp_amount ?? 0);
            if($remaining>0){
                DB::table('cash_ledger')->insert([
                    'booking_id'=>$order->id,
                    'type'=>'dp_remaining_in',
                    'amount'=>$remaining,
                    'note'=>'Pelunasan sisa DP',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                // Update dp_amount to reflect fully paid if desired
                $order->dp_amount = (int)$order->total_harga;
                $order->save();
            }
        }
        // If marked dp_cancel, log a dp_canceled entry with current dp_amount
        if($order->payment_status==='dp_cancel' && (int)($order->dp_amount ?? 0) > 0){
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_canceled',
                'amount'=>(int)$order->dp_amount,
                'note'=>'DP dibatalkan',
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }

        if($request->wantsJson()){
            $meta = $this->statusMeta($order->status);
            return response()->json([
                'success'=>true,
                'message'=>'Booking diperbarui',
                'order'=>[
                    'id'=>$order->id,
                    'status_key'=>$meta['key'],
                    'status_label'=>$meta['label'],
                    'tanggal_checkin'=>$order->tanggal_checkin,
                    'tanggal_checkout'=>$order->tanggal_checkout,
                    'total_harga'=>$order->total_harga,
                    'jumlah_tamu_total'=>$order->jumlah_tamu_total,
                    'pelanggan_id'=>$order->pelanggan_id,
                    'status'=>$order->status,
                    'payment_status'=>$order->payment_status,
                    'dp_percentage'=>$order->dp_percentage,
                ]
            ]);
        }
        return redirect()->route('booking.detail', $id)->with('success','Booking berhasil diperbarui');
    }

    /**
     * Update harga per item booking order tanpa mengubah harga asli kamar.
     * Input: items: [ {id: booking_order_item_id, harga_per_malam: int|null, subtotal: int|null} ]
     * Jika hanya harga_per_malam diberikan, subtotal akan dihitung ulang (malam * harga_per_malam).
     * Jika subtotal diberikan langsung, dipakai apa adanya (override manual / diskon).
     */
    public function updatePrices(Request $request, $id)
    {
        $order = BookingOrder::with('items')->findOrFail($id);
        $payload = $request->validate([
            'items'=>'required|array|min:1',
            'items.*.id'=>'required|integer|exists:booking_order_items,id',
            'items.*.harga_per_malam'=>'nullable|integer|min:0',
            'items.*.subtotal'=>'nullable|integer|min:0'
        ]);

        // Pastikan item milik order ini
        $allowedIds = $order->items->pluck('id')->toArray();
        $updateMap = collect($payload['items'])->keyBy('id');
        foreach($updateMap as $itemId=>$vals){
            if(!in_array($itemId,$allowedIds)){
                return response()->json(['success'=>false,'message'=>'Item tidak sesuai dengan booking'],422);
            }
        }

        $total = 0;
        foreach($order->items as $it){
            if(isset($updateMap[$it->id])){
                $vals = $updateMap[$it->id];
                $changed = false;
                if(array_key_exists('harga_per_malam',$vals) && $vals['harga_per_malam'] !== null){
                    $it->harga_per_malam = (int)$vals['harga_per_malam'];
                    // Recalc subtotal jika subtotal eksplisit tidak dikirim
                    if(!array_key_exists('subtotal',$vals) || $vals['subtotal'] === null){
                        $it->subtotal = $it->malam * $it->harga_per_malam;
                    }
                    $changed = true;
                }
                if(array_key_exists('subtotal',$vals) && $vals['subtotal'] !== null){
                    $it->subtotal = (int)$vals['subtotal'];
                    $changed = true;
                }
                if($changed){ $it->save(); }
            }
            $total += $it->subtotal;
        }
        $order->total_harga = $total;
        $order->save();

        if($request->wantsJson()){
            return response()->json([
                'success'=>true,
                'message'=>'Harga berhasil diperbarui',
                'order'=>[
                    'id'=>$order->id,
                    'total_harga'=>$order->total_harga,
                    'items'=>$order->items->map(fn($i)=> [
                        'id'=>$i->id,
                        'malam'=>$i->malam,
                        'harga_per_malam'=>$i->harga_per_malam,
                        'subtotal'=>$i->subtotal,
                    ])
                ]
            ]);
        }
        return redirect()->back()->with('success','Harga booking diperbarui');
    }

    /**
     * Hapus booking beserta items & cafe orders.
     */
    public function destroy(Request $request, $id)
    {
        $order = BookingOrder::with(['items','cafeOrders.items'])->findOrFail($id);
        // Hapus hierarki manual (jika belum cascade di DB)
        foreach($order->cafeOrders as $co){ $co->items()->delete(); $co->delete(); }
        $order->items()->delete();
        $order->delete();
        if($request->wantsJson()){
            return response()->json(['success'=>true,'deleted_id'=>$id]);
        }
        return redirect()->back()->with('success','Booking dihapus');
    }

    /**
     * Cetak nota sederhana: total kamar dan total cafe saja, beserta grand total.
     */
    public function printNota(Request $request, $id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar','cafeOrders.items.product'])->findOrFail($id);
        $roomTotal = (float)($order->total_harga ?? 0);
        $cafeTotal = (float)($order->total_cafe ?? 0);
        $diskon = (float)($order->diskon ?? 0);
        $biayaTambahan = (float)($order->biaya_tambahan ?? 0);
        $subtotal = $roomTotal + $cafeTotal;
        $grand = $subtotal - $diskon + $biayaTambahan;
        return view('nota', [
            'order'=>$order,
            'roomTotal'=>$roomTotal,
            'cafeTotal'=>$cafeTotal,
            'diskon'=>$diskon,
            'biayaLain'=>$biayaTambahan,
            'grandTotal'=>$grand,
        ]);
    }

    /** Editable invoice for only room (booking) portion */
    public function notaBooking(Request $request, $id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar'])->findOrFail($id);
        return view('nota_booking', ['order'=>$order]);
    }

    /** Generate printout for guest */
    public function printout(Request $request, $id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar'])->findOrFail($id);
        return view('booking_printout', ['order'=>$order]);
    }

    /** Editable invoice for cafe portion linked to a booking */
    public function notaCafe(Request $request, $id)
    {
        $order = BookingOrder::with(['pelanggan','cafeOrders.items.product'])->findOrFail($id);
        // Flatten cafe items across orders
        $cafeItems = collect();
        foreach(($order->cafeOrders ?? []) as $co){
            foreach(($co->items ?? []) as $it){
                $cafeItems->push($it);
            }
        }
        return view('nota_cafe', [
            'order'=>$order,
            'cafeItems'=>$cafeItems,
        ]);
    }

    // Page-based detail view (replaces modal)
    public function detailPage($id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar','cafeOrders.items.product'])->findOrFail($id);
        // Other orders by same customer for quick history
        $other = collect();
        if($order->pelanggan_id){
            $other = BookingOrder::where('pelanggan_id',$order->pelanggan_id)
                ->where('id','!=',$order->id)
                ->orderByDesc('tanggal_checkin')
                ->limit(10)
                ->get();
        }
        return view('booking_detail', [
            'order' => $order,
            'otherOrders' => $other,
        ]);
    }

    // Page-based edit view
    public function editPage($id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar'])->findOrFail($id);
        $pelangganList = Pelanggan::orderBy('nama')->get();
        return view('booking_edit', [
            'order' => $order,
            'pelangganList' => $pelangganList,
        ]);
    }

    public function create()
    {
        $pelangganList = Pelanggan::orderBy('nama')->get();
        $availableKamar = Kamar::orderBy('tipe')->orderBy('nomor_kamar')->get();

        return view('booking_create', compact('pelangganList', 'availableKamar'));
    }

    public function penginap()
    {
        // Use pagination so the view can call onEachSide()->links()
        $penginap = Pelanggan::orderBy('nama')->paginate(10);
        return view('penginap', compact('penginap'));
    }

    public function penginapcreate(Request $request)
    {
        // Validasi input (gunakan nama field yang konsisten dengan model Pelanggan)
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:20',
            'alamat' => 'required|string|max:500',
            'jenis_identitas' => 'nullable|string|max:100',
            'nomor_identitas' => 'nullable|string|max:100',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'kewarganegaraan' => 'nullable|string|max:100',
        ]);

        // Simpan data pelanggan baru
        Pelanggan::create([
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jenis_identitas' => ($validated['jenis_identitas'] ?? null) === 'LAIN'
                ? ($validated['jenis_identitas_lain'] ?? 'Lain')
                : ($validated['jenis_identitas'] ?? null),
            'nomor_identitas' => $validated['nomor_identitas'] ?? null,
            'tempat_lahir' => $validated['tempat_lahir'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'kewarganegaraan' => $validated['kewarganegaraan'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }
    public function penginapedit(Request $request)
    {
        // Validasi input menggunakan named error bag 'edit'
        $validator = \Validator::make($request->all(), [
            'id' => 'required|exists:pelanggan,id',
            'nama' => 'required|string|max:255',
            'telepon' => 'required|string|max:20',
            'alamat' => 'required|string|max:500',
            'jenis_identitas' => 'nullable|string|max:100',
            'nomor_identitas' => 'nullable|string|max:100',
            'tempat_lahir' => 'nullable|string|max:100',
            'tanggal_lahir' => 'nullable|date',
            'kewarganegaraan' => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'edit')->withInput();
        }
        $validated = $validator->validated();

        // Update data pelanggan
        $pelanggan = Pelanggan::findOrFail($validated['id']);
        $pelanggan->update([
            'nama' => $validated['nama'],
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jenis_identitas' => $validated['jenis_identitas'] ?? null,
            'nomor_identitas' => $validated['nomor_identitas'] ?? null,
            'tempat_lahir' => $validated['tempat_lahir'] ?? null,
            'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
            'kewarganegaraan' => $validated['kewarganegaraan'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Data pelanggan berhasil diperbarui.');
    }
    public function penginapdestroy($id)
    {
        // Hapus data pelanggan
        $pelanggan = Pelanggan::find($id);
        if ($pelanggan) {
            $pelanggan->delete();
            return redirect()->back()->with('success', 'Data pelanggan berhasil dihapus.');
        }
        return redirect()->back()->with('error', 'Pelanggan tidak ditemukan.');
    }
}