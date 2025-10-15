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
use App\Models\BookingRoomTransfer;
use Illuminate\Support\Facades\Auth;


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
     * Tambah cashback (pendapatan tambahan) untuk sebuah booking.
     * Tidak memengaruhi perhitungan total_harga booking; hanya tercatat di ledger dan ikut rekap.
     */
    public function addCashback(Request $request, $id)
    {
        $order = BookingOrder::findOrFail($id);
        $data = $request->validate([
            'amount' => 'required|integer|min:0',
            'note'   => 'nullable|string|max:190',
        ]);
        \DB::table('cash_ledger')->insert([
            'booking_id' => $order->id,
            'type'       => 'cashback_in',
            'amount'     => (int)$data['amount'],
            'note'       => $data['note'] ? $data['note'] : 'Cashback',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        if ($request->wantsJson()) {
            return response()->json(['success'=>true]);
        }
        return back()->with('success','Cashback ditambahkan');
    }

    /**
     * Simpan booking baru (walk-in atau online)
     */
    public function store(Request $request)
    {
        // Normalize monetary inputs (if client-side formatting slipped through)
        $toInt = function($v){ if($v===null||$v==='') return $v; $s = preg_replace('/[^0-9]/','',(string)$v); return $s===null||$s===''? 0 : (int)$s; };
        $request->merge([
            'dp_amount' => $toInt($request->get('dp_amount')),
            'biaya_tambahan' => $toInt($request->get('biaya_tambahan')),
        ]);
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
            // Overlap rule using half-open intervals: [start, end)
            // Conflict exists if existing.checkin < selected.end AND existing.checkout > selected.start
            $conflict = BookingOrderItem::where('kamar_id',$k->id)
                ->whereHas('order', function($q) use ($startCheck,$endCheck){
                    $q->whereIn('status',[1,2])
                      ->where(function($qq) use ($startCheck,$endCheck){
                          $qq->where('tanggal_checkin','<',$endCheck)
                             ->where('tanggal_checkout','>',$startCheck);
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
        // Compute raw day and hour differences
        // Nights counted based on calendar days difference (ignore clock time)
        $rawDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());
        $days = max($rawDays,1);

    // Default lifecycle: walk-in defaults to checkin (2), online defaults to dipesan (1)
    $status = isset($data['status']) ? (int)$data['status'] : ((int)$data['pemesanan'] == 0 ? 2 : 1);

        // Base total from selected rooms * nights
        $baseTotal = 0;
        foreach($kamarList as $k){
            $baseTotal += $days * (int)$k->harga;
        }
        // Robust half-day check: same calendar day and <= 360 minutes
        $halfDay = $startCheck->isSameDay($endCheck) && ($endCheck->diffInMinutes($startCheck) <= 360);
        if ($halfDay) {
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

        try {
            DB::beginTransaction();
            // Generate next daily booking number atomically (resets each day)
            DB::statement("INSERT INTO booking_sequences (seq_date, counter, created_at, updated_at) VALUES (CURRENT_DATE, 1, NOW(), NOW()) ON DUPLICATE KEY UPDATE counter = LAST_INSERT_ID(counter + 1), updated_at = VALUES(updated_at)");
            $seq = DB::selectOne("SELECT LAST_INSERT_ID() AS next_counter");
            $nextCounter = (int)($seq->next_counter ?? 1);
            $bookingNumber = 'BKG-' . now()->format('Ymd') . '-' . str_pad((string)$nextCounter, 4, '0', STR_PAD_LEFT);

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
                // New human-friendly booking number
                'booking_number' => $bookingNumber,
            ]);

            foreach($kamarList as $k){
                $subtotal = $days * (int)$k->harga;
                // Apply half-day adjustment per item when halfDay
                if ($halfDay) { $subtotal = (int) round($subtotal * 0.5); }
                BookingOrderItem::create([
                    'booking_order_id' => $order->id,
                    'kamar_id' => $k->id,
                    'malam' => $days,
                    'harga_per_malam' => (int)$k->harga,
                    'subtotal' => $subtotal,
                ]);
            }

            // Ledger: DP in
            if($dpAmount > 0){
                $dpMethod = strtolower((string)($request->get('dp_payment_method') ?? $paymentMethod ?? '')) ?: null;
                DB::table('cash_ledger')->insert([
                    'booking_id' => $order->id,
                    'type' => 'dp_in',
                    'amount' => $dpAmount,
                    'note' => 'Uang masuk DP',
                    'payment_method' => $dpMethod,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Create booking failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('booking.index')
                ->withErrors(['general' => 'Terjadi kesalahan saat membuat booking. Silakan coba lagi.'], 'booking_create')
                ->withInput();
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
        $oldPayment = (string)($booking->payment_status ?? 'dp');
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
        // Ledger: if payment becomes lunas here, log remaining pelunasan
        if($oldPayment !== 'lunas' && $booking->payment_status === 'lunas'){
            $total = (int)($booking->total_harga ?? 0);
            $dp = (int)($booking->dp_amount ?? 0);
            $remaining = max(0, $total - $dp);
            if($remaining > 0){
                DB::table('cash_ledger')->insert([
                    'booking_id'=>$booking->id,
                    'type'=>'dp_remaining_in',
                    'amount'=>$remaining,
                    'note'=>'Pelunasan sisa DP (checkout)',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                $booking->dp_amount = $dp + $remaining;
                $booking->save();
            }
        }
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
            // Use authoritative totals from order (already includes discounts/half-day/per-head)
            $roomTotal = (int)($order->total_harga ?? 0);
            $cafeTotal = (int)($order->total_cafe ?? 0);
            $diskon = (int)($order->diskon ?? 0);
            $biayaTambahan = (int)($order->biaya_tambahan ?? 0);
            // Avoid double-subtracting discount: total_harga is AFTER discounts
            $grand = max(0, $roomTotal + $cafeTotal + $biayaTambahan);
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
                'total_harga'=>$roomTotal,
                'total_cafe'=>$cafeTotal,
                'biaya_tambahan'=>$biayaTambahan,
                'diskon'=>$diskon,
                'grand_total'=>$grand,
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
        $old = (string)($order->payment_status ?? 'dp');
        $order->payment_status = $new;
        $order->save();
        // Ledger effects when toggling payment status directly
        if($old !== 'lunas' && $new === 'lunas'){
            $total = (int)($order->total_harga ?? 0);
            $dp = (int)($order->dp_amount ?? 0);
            $remaining = max(0, $total - $dp);
            if($remaining > 0){
                DB::table('cash_ledger')->insert([
                    'booking_id'=>$order->id,
                    'type'=>'dp_remaining_in',
                    'amount'=>$remaining,
                    'note'=>'Pelunasan sisa DP (toggle)',
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                $order->dp_amount = $dp + $remaining;
                $order->save();
            }
        }
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
        // Normalize monetary inputs (if client-side formatting slipped through)
        $toInt = function($v){ if($v===null||$v==='') return $v; $s = preg_replace('/[^0-9]/','',(string)$v); return $s===null||$s===''? 0 : (int)$s; };
        $request->merge([
            'dp_amount' => $toInt($request->get('dp_amount')),
            'biaya_tambahan' => $toInt($request->get('biaya_tambahan')),
        ]);
        $order = BookingOrder::with(['items.kamar','cafeOrders'])->findOrFail($id);
        // Capture old values for ledger reconciliation
        $oldDp = (int)($order->dp_amount ?? 0);
        $oldTotal = (int)($order->total_harga ?? 0);
        $oldPayment = (string)($order->payment_status ?? 'dp');
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
        // Calculate raw day span and derived nights (calendar days, ignore clock time)
        $rawDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());
        $days = max($rawDays,1);

        // Base recalc from authoritative kamar nightly rates
        $base = 0;
        foreach($order->items as $it){
            // Always pull price from kamar table; remove manual overrides
            $it->harga_per_malam = (int)($it->kamar?->harga ?? $it->harga_per_malam ?? 0);
            $it->malam = $days;
            $it->subtotal = $days * (int)$it->harga_per_malam;
            $it->save();
            $base += $it->subtotal;
        }
        // Apply automatic half-day adjustment only when same calendar day and <= 360 minutes
        $halfDay = $start->isSameDay($end) && ($end->diffInMinutes($start) <= 360);
        if ($halfDay) {
            $base = (int) round($base * 0.5);
            foreach($order->items as $it){
                $it->subtotal = (int) round($it->subtotal * 0.5);
                $it->save();
            }
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

        // Ledger logic (reconcile changes after edit)
        $newDp = (int)($order->dp_amount ?? 0);
        $newTotal = (int)($order->total_harga ?? 0);
        $newPayment = (string)($order->payment_status ?? 'dp');

        // 1) Record DP additions (manual increase in dp_amount)
        if($newDp > $oldDp){
            $delta = $newDp - $oldDp;
            $dpMethod = strtolower((string)($request->get('dp_payment_method') ?? $request->get('payment_method') ?? $order->payment_method ?? '')) ?: null;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_in',
                'amount'=>$delta,
                'note'=>'Tambahan DP',
                'payment_method' => $dpMethod,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
        // 2) Record DP reductions (manual decrease in dp_amount)
        if($newDp < $oldDp){
            $delta = $oldDp - $newDp;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_canceled',
                'amount'=>$delta,
                'note'=>'Koreksi pengurangan DP',
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
        // 3) If payment transitions to lunas, log remaining top-up if any
        if($oldPayment !== 'lunas' && $newPayment === 'lunas'){
            $remaining = max(0, $newTotal - $newDp);
            if($remaining > 0){
                $pelunasanMethod = strtolower((string)($request->get('pelunasan_payment_method') ?? $request->get('payment_method') ?? $order->payment_method ?? '')) ?: null;
                DB::table('cash_ledger')->insert([
                    'booking_id'=>$order->id,
                    'type'=>'dp_remaining_in',
                    'amount'=>$remaining,
                    'note'=>'Pelunasan sisa DP',
                    'payment_method' => $pelunasanMethod,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                // Align dp_amount to total
                $order->dp_amount = $newDp + $remaining;
                $order->save();
                $newDp = (int)$order->dp_amount;
            }
        }
        // 4) If already lunas and total increases beyond current dp, log top-up
        if($newPayment === 'lunas' && $newTotal > $newDp){
            $delta = $newTotal - $newDp;
            $pelunasanMethod = strtolower((string)($request->get('pelunasan_payment_method') ?? $request->get('payment_method') ?? $order->payment_method ?? '')) ?: null;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_remaining_in',
                'amount'=>$delta,
                'note'=>'Penyesuaian total (top-up)',
                'payment_method' => $pelunasanMethod,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $order->dp_amount = $newDp + $delta;
            $order->save();
        }
        // 4b) If already lunas and total decreases below current dp, log refund/correction
        if($newPayment === 'lunas' && $newTotal < $newDp){
            $delta = $newDp - $newTotal;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_canceled',
                'amount'=>$delta,
                'note'=>'Penyesuaian total (refund/koreksi)',
                'payment_method' => strtolower((string)($request->get('pelunasan_payment_method') ?? $order->payment_method ?? '')) ?: null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $order->dp_amount = $newDp - $delta;
            $order->save();
        }
        // 5) Explicit dp_cancel: record full current DP as canceled
        if($newPayment === 'dp_cancel' && $newDp > 0){
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_canceled',
                'amount'=>$newDp,
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
        // Ignore client overrides; enforce price from kamar table
        $order = BookingOrder::with('items.kamar')->findOrFail($id);
        $total = 0;
        foreach($order->items as $it){
            $it->harga_per_malam = (int)($it->kamar?->harga ?? 0);
            $it->subtotal = (int)$it->malam * (int)$it->harga_per_malam;
            $it->save();
            $total += $it->subtotal;
        }
        $order->total_harga = $total;
        $order->save();

        if($request->wantsJson()){
            return response()->json([
                'success'=>true,
                'message'=>'Harga diambil otomatis dari data kamar',
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
        return redirect()->back()->with('success','Harga booking diset dari data kamar');
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
        $roomTransfers = BookingRoomTransfer::with(['fromKamar','toKamar','actor','item'])
            ->where('booking_id', $order->id)
            ->orderByDesc('id')
            ->get();
        return view('booking_detail', [
            'order' => $order,
            'otherOrders' => $other,
            'roomTransfers' => $roomTransfers,
        ]);
    }

    // Page-based edit view
    public function editPage($id)
    {
        $order = BookingOrder::with(['pelanggan','items.kamar'])->findOrFail($id);
        $pelangganList = Pelanggan::orderBy('nama')->get();
        // Hitung kamar tersedia untuk rentang tanggal order ini (exclude order ini)
        $start = Carbon::parse($order->tanggal_checkin);
        $end = Carbon::parse($order->tanggal_checkout);
        $conflictingItems = BookingOrderItem::whereHas('order', function($q) use ($order,$start,$end){
                $q->whereIn('status',[1,2])
                  ->where('id','!=',$order->id)
                  ->where(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<',$end)
                         ->where('tanggal_checkout','>',$start);
                  });
            })
            ->pluck('kamar_id')
            ->all();
        $occupied = array_fill_keys($conflictingItems, true);
        $availableKamar = Kamar::orderBy('tipe')->orderBy('nomor_kamar')->get()
            ->filter(fn($k) => !isset($occupied[$k->id]))
            ->values();
        return view('booking_edit', [
            'order' => $order,
            'pelangganList' => $pelangganList,
            'availableKamar' => $availableKamar,
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

    /**
     * Pindah kamar untuk sebuah item booking (misal kamar bermasalah).
     * Input: item_id, new_kamar_id
     */
    public function moveRoom(Request $request, $orderId)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:booking_order_items,id',
            'new_kamar_id' => 'required|exists:kamar,id',
        ]);
        $order = BookingOrder::with('items.kamar')->findOrFail($orderId);
        $item = $order->items->firstWhere('id', (int)$data['item_id']);
        if(!$item){
            return back()->with('error','Item booking tidak ditemukan di order ini');
        }
        $newKamar = Kamar::findOrFail((int)$data['new_kamar_id']);
        if($item->kamar_id == $newKamar->id){
            return back()->with('error','Kamar baru sama dengan kamar sekarang');
        }

        // Cek konflik jadwal untuk kamar baru pada rentang order ini
        $start = Carbon::parse($order->tanggal_checkin);
        $end = Carbon::parse($order->tanggal_checkout);
        $conflict = BookingOrderItem::where('kamar_id',$newKamar->id)
            ->whereHas('order', function($q) use ($order,$start,$end){
                $q->whereIn('status',[1,2])
                  ->where('id','!=',$order->id)
                  ->where(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<',$end)
                         ->where('tanggal_checkout','>',$start);
                  });
            })
            ->exists();
        if($conflict){
            return back()->with('error','Kamar tujuan sedang terpakai pada tanggal tersebut');
        }

        // Simpan nilai sebelum perubahan untuk rekonsiliasi ledger
        $oldDp = (int)($order->dp_amount ?? 0);
        $oldTotal = (int)($order->total_harga ?? 0);
        $oldPayment = (string)($order->payment_status ?? 'dp');

        // Hitung malam dan half-day
        $rawDays = $start->diffInDays($end);
        $days = max($rawDays,1);
        $diffHours = $end->diffInHours($start);

        // Terapkan pindah kamar pada item: HARGA TETAP (gunakan harga_per_malam saat ini)
        $fromKamarId = $item->kamar_id;
        $oldRoomNo = $item->kamar?->nomor_kamar;
        $oldPricePerMalam = (int)($item->harga_per_malam ?? 0);
        $item->kamar_id = $newKamar->id;
        $item->malam = $days;
        $item->subtotal = $days * (int)$item->harga_per_malam;
        // Half-day adjustment per item jika berlaku
        $halfDay = $start->isSameDay($end) && ($end->diffInMinutes($start) <= 360);
        if ($halfDay) { $item->subtotal = (int) round($item->subtotal * 0.5); }
        $item->save();

        // Re-hitung BASE dari semua item TANPA mengubah harga_per_malam kecuali sesuaikan malam/subtotal
        $order->load('items.kamar');
        $base = 0;
        foreach($order->items as $it){
            $it->malam = $days;
            $it->subtotal = $days * (int)$it->harga_per_malam;
            if ($halfDay) { $it->subtotal = (int) round($it->subtotal * 0.5); }
            $it->save();
            $base += (int)$it->subtotal;
        }

        // Per-head dan diskon
        $perHead = (bool)($order->per_head_mode ?? false);
        $jumlahTamu = (int)($order->jumlah_tamu_total ?? 1);
        if($perHead && $jumlahTamu>2){ $base += 50000 * ($jumlahTamu - 2); }
        $base = max($base, 100000);
        $after = $base;
        if($order->discount_review){ $after = (int) round($after * 0.9); }
        if($order->discount_follow){ $after = (int) round($after * 0.9); }
        $diskonNom = max(0, $base - $after);

        // Set total baru ke order
        $order->total_harga = $after;
        $order->diskon = $diskonNom;
        $order->save();

        // Rekonsiliasi ledger jika perlu
        $newDp = (int)($order->dp_amount ?? 0);
        $newTotal = (int)($order->total_harga ?? 0);
        $newPayment = (string)($order->payment_status ?? 'dp');
        if($oldPayment !== 'lunas' && $newPayment === 'lunas'){
            $remaining = max(0, $newTotal - $newDp);
            if($remaining > 0){
                DB::table('cash_ledger')->insert([
                    'booking_id'=>$order->id,
                    'type'=>'dp_remaining_in',
                    'amount'=>$remaining,
                    'note'=>'Pelunasan sisa DP (pindah kamar)',
                    'payment_method' => strtolower((string)($order->payment_method ?? '')) ?: null,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                $order->dp_amount = $newDp + $remaining;
                $order->save();
                $newDp = (int)$order->dp_amount;
            }
        }
        if($newPayment === 'lunas' && $newTotal > $newDp){
            $delta = $newTotal - $newDp;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_remaining_in',
                'amount'=>$delta,
                'note'=>'Penyesuaian total (pindah kamar)',
                'payment_method' => strtolower((string)($order->payment_method ?? '')) ?: null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $order->dp_amount = $newDp + $delta;
            $order->save();
        }
        if($newPayment === 'lunas' && $newTotal < $newDp){
            $delta = $newDp - $newTotal;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_canceled',
                'amount'=>$delta,
                'note'=>'Koreksi total (pindah kamar)',
                'payment_method' => strtolower((string)($order->payment_method ?? '')) ?: null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $order->dp_amount = $newDp - $delta;
            $order->save();
        }

        // Log transfer history
        BookingRoomTransfer::create([
            'booking_id' => $order->id,
            'booking_order_item_id' => $item->id,
            'from_kamar_id' => $fromKamarId,
            'to_kamar_id' => $newKamar->id,
            'action' => 'move',
            'old_price_per_malam' => $oldPricePerMalam,
            'new_price_per_malam' => (int)$item->harga_per_malam,
            'old_total' => $oldTotal,
            'new_total' => (int)$order->total_harga,
            'actor_user_id' => Auth::id(),
            'note' => $request->get('note'),
        ]);

        if($request->wantsJson()){
            return response()->json(['success'=>true,'order_id'=>$order->id,'new_total'=>$order->total_harga]);
        }
        $newRoomNo = $newKamar->nomor_kamar;
        return back()->with(
            'success',
            'Kamar berhasil dipindahkan: '.($oldRoomNo ?? '-').' → '.($newRoomNo ?? '-').'. Total: Rp'.number_format((int)$order->total_harga,0,',','.')
        );
    }

    /**
     * Upgrade kamar: sama seperti pindah kamar namun memastikan harga baru >= harga lama.
     * Input: item_id, new_kamar_id
     */
    public function upgradeRoom(Request $request, $orderId)
    {
        $data = $request->validate([
            'item_id' => 'required|exists:booking_order_items,id',
            'new_kamar_id' => 'required|exists:kamar,id',
        ]);
        $order = BookingOrder::with('items.kamar')->findOrFail($orderId);
        $oldTotalBeforeUpgrade = (int)($order->total_harga ?? 0);
        $item = $order->items->firstWhere('id', (int)$data['item_id']);
        if(!$item){ return back()->with('error','Item booking tidak ditemukan di order ini'); }
        $newKamar = Kamar::findOrFail((int)$data['new_kamar_id']);
        if($item->kamar_id == $newKamar->id){ return back()->with('error','Kamar baru sama dengan kamar sekarang'); }
        if((int)$newKamar->harga < (int)($item->kamar?->harga ?? $item->harga_per_malam ?? 0)){
            return back()->with('error','Upgrade harus ke kamar dengan harga lebih tinggi atau sama');
        }
        // Logika upgrade: Ganti kamar_id dan gunakan HARGA BARU untuk item ini.
        $start = Carbon::parse($order->tanggal_checkin);
        $end = Carbon::parse($order->tanggal_checkout);
        $rawDays = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());
        $days = max($rawDays,1);
        $halfDay = $start->isSameDay($end) && ($end->diffInMinutes($start) <= 360);

        // Update item yang diupgrade: terapkan harga kamar BARU
        $fromKamarId = $item->kamar_id;
        $oldPricePerMalam = (int)($item->harga_per_malam ?? 0);
        $item->kamar_id = $newKamar->id;
        $item->harga_per_malam = (int)$newKamar->harga;
        $item->malam = $days;
        $item->subtotal = $days * (int)$item->harga_per_malam;
        if ($halfDay) { $item->subtotal = (int) round($item->subtotal * 0.5); }
        $item->save();

        // Rehitung total: item lain TIDAK diubah harganya; hanya malam/subtotal disesuaikan
        $order->load('items.kamar');
        $base = 0;
        foreach($order->items as $it){
            // Jangan override harga_per_malam untuk item lain
            if ($it->id !== $item->id) {
                $it->malam = $days;
                $it->subtotal = $days * (int)$it->harga_per_malam;
                if ($halfDay) { $it->subtotal = (int) round($it->subtotal * 0.5); }
                $it->save();
            }
            $base += (int)$it->subtotal;
        }

        // Per-head dan diskon
        $perHead = (bool)($order->per_head_mode ?? false);
        $jumlahTamu = (int)($order->jumlah_tamu_total ?? 1);
        if($perHead && $jumlahTamu>2){ $base += 50000 * ($jumlahTamu - 2); }
        $base = max($base, 100000);
        $after = $base;
        if($order->discount_review){ $after = (int) round($after * 0.9); }
        if($order->discount_follow){ $after = (int) round($after * 0.9); }
        $diskonNom = max(0, $base - $after);

        $oldDp = (int)($order->dp_amount ?? 0);
        $oldPayment = (string)($order->payment_status ?? 'dp');
        $order->total_harga = $after;
        $order->diskon = $diskonNom;
        $order->save();

        // Log transfer history for upgrade
        BookingRoomTransfer::create([
            'booking_id' => $order->id,
            'booking_order_item_id' => $item->id,
            'from_kamar_id' => $fromKamarId,
            'to_kamar_id' => $newKamar->id,
            'action' => 'upgrade',
            'old_price_per_malam' => $oldPricePerMalam,
            'new_price_per_malam' => (int)$item->harga_per_malam,
            'old_total' => $oldTotalBeforeUpgrade,
            'new_total' => (int)$order->total_harga,
            'actor_user_id' => Auth::id(),
            'note' => $request->get('note'),
        ]);

        // Rekonsiliasi ledger untuk upgrade
        $newDp = (int)($order->dp_amount ?? 0);
        $newTotal = (int)($order->total_harga ?? 0);
        $newPayment = (string)($order->payment_status ?? 'dp');
        if($oldPayment !== 'lunas' && $newPayment === 'lunas'){
            $remaining = max(0, $newTotal - $newDp);
            if($remaining > 0){
                DB::table('cash_ledger')->insert([
                    'booking_id'=>$order->id,
                    'type'=>'dp_remaining_in',
                    'amount'=>$remaining,
                    'note'=>'Pelunasan sisa DP (upgrade kamar)',
                    'payment_method' => strtolower((string)($order->payment_method ?? '')) ?: null,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                $order->dp_amount = $newDp + $remaining;
                $order->save();
                $newDp = (int)$order->dp_amount;
            }
        }
        if($newPayment === 'lunas' && $newTotal > $newDp){
            $delta = $newTotal - $newDp;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_remaining_in',
                'amount'=>$delta,
                'note'=>'Penyesuaian total (upgrade kamar)',
                'payment_method' => strtolower((string)($order->payment_method ?? '')) ?: null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $order->dp_amount = $newDp + $delta;
            $order->save();
        }
        if($newPayment === 'lunas' && $newTotal < $newDp){
            $delta = $newDp - $newTotal;
            DB::table('cash_ledger')->insert([
                'booking_id'=>$order->id,
                'type'=>'dp_canceled',
                'amount'=>$delta,
                'note'=>'Koreksi total (upgrade kamar)',
                'payment_method' => strtolower((string)($order->payment_method ?? '')) ?: null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
            $order->dp_amount = $newDp - $delta;
            $order->save();
        }

        if($request->wantsJson()){
            return response()->json(['success'=>true,'order_id'=>$order->id,'new_total'=>$order->total_harga]);
        }
        return back()->with('success','Kamar berhasil di-upgrade dan total diperbarui');
    }
}