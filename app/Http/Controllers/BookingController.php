<?php

namespace App\Http\Controllers;
use App\Models\Booking; // legacy (masih ada untuk kompatibilitas sementara)
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use App\Models\Pelanggan;
use App\Models\Kamar;
use Illuminate\Http\Request;
use Carbon\Carbon;


class BookingController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->get('tanggal', date('Y-m-d'));
        $start = Carbon::parse($tanggal)->startOfDay();
        $end = Carbon::parse($tanggal)->endOfDay();

        $kamarAll = Kamar::orderBy('tipe')->orderBy('nomor_kamar')->get();

        // Ambil order aktif (status 1/2) yang overlap tanggal tersebut beserta items & pelanggan
        $activeOrders = BookingOrder::with(['pelanggan','items.kamar'])
            ->whereIn('status',[1,2])
            ->where(function($q) use ($start,$end){
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                          ->where('tanggal_checkout','>=',$end);
                  });
            })
            ->get();

        // Pemetaan kamar_id -> order aktif (ambil prioritas status checkin > dipesan)
        $kamarToOrder = [];
        foreach($activeOrders as $order){
            foreach($order->items as $item){
                $existing = $kamarToOrder[$item->kamar_id] ?? null;
                if(!$existing){
                    $kamarToOrder[$item->kamar_id] = $order;
                } else {
                    // Prioritaskan status 2 (checkin)
                    if($existing->status != 2 && $order->status == 2){
                        $kamarToOrder[$item->kamar_id] = $order;
                    }
                }
            }
        }

        $rooms = $kamarAll->map(function($room) use ($kamarToOrder){
            $activeOrder = $kamarToOrder[$room->id] ?? null;
            // Samakan interface dengan view (activeBooking)
            $activeBooking = null;
            if($activeOrder){
                // Bungkus agar property serupa dengan model Booking lama dipakai view
                $activeBooking = $activeOrder; // memiliki: status, tanggal_checkin, tanggal_checkout, pelanggan, pemesanan, total_harga
            }
            return ['room'=>$room,'activeBooking'=>$activeBooking];
        });
        $groupedKamar = $rooms->groupBy(fn($item)=> $item['room']->tipe ?? 'Lain');

        $pelangganList = Pelanggan::orderBy('nama')->get();

        // Kamar tersedia: tidak ada order aktif untuk kamar itu
        $occupiedIds = array_keys($kamarToOrder);
        $availableKamar = $kamarAll->filter(fn($k)=> !in_array($k->id,$occupiedIds))->values();

        return view('booking', [
            'groupedKamar'=>$groupedKamar,
            'tanggal'=>$tanggal,
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
            'pemesanan'         => 'required|in:0,1',
            'catatan'           => 'nullable|string',
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

        $status = $data['pemesanan'] == 0 ? 2 : 1; // walk-in -> langsung checkin

        $totalOrder = 0;
        foreach($kamarList as $k){
            $totalOrder += $days * (int)$k->harga;
        }

        $order = BookingOrder::create([
            'pelanggan_id' => $data['pelanggan_id'],
            'tanggal_checkin' => $start,
            'tanggal_checkout' => $end,
            'jumlah_tamu_total' => $data['jumlah_tamu'],
            'status' => $status,
            'pemesanan' => (int)$data['pemesanan'],
            'catatan' => $data['catatan'] ?? null,
            'total_harga' => $totalOrder,
        ]);

        foreach($kamarList as $k){
            BookingOrderItem::create([
                'booking_order_id' => $order->id,
                'kamar_id' => $k->id,
                'malam' => $days,
                'harga_per_malam' => (int)$k->harga,
                'subtotal' => $days * (int)$k->harga,
            ]);
            if($status === 2){
                $k->update(['status'=>2]);
            }
        }

        return redirect()->route('booking.index', ['tanggal'=>$start->format('Y-m-d')])
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
        $allowed = ['checkin','checkout','cancel'];
        if (!in_array($action, $allowed)) {
            return redirect()->back()->with('error', 'Aksi tidak dikenal');
        }
        switch ($action) {
            case 'checkin':
                if ($booking->status != 1) return redirect()->back()->with('error','Tidak dapat check-in');
                $booking->status = 2;
                foreach($booking->items as $it){ $it->kamar?->update(['status'=>2]); }
                break;
            case 'checkout':
                if ($booking->status != 2) return redirect()->back()->with('error','Tidak dapat check-out');
                $booking->status = 3;
                foreach($booking->items as $it){ $it->kamar?->update(['status'=>1]); }
                break;
            case 'cancel':
                if (!in_array($booking->status, [1])) return redirect()->back()->with('error','Tidak dapat membatalkan');
                $booking->status = 4;
                foreach($booking->items as $it){ $it->kamar?->update(['status'=>1]); }
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

    // (CRUD detail methods omitted – using modal-based create/update flows on index)
    public function penginap()
    {
        $penginap = Pelanggan::paginate(10);
        return view('penginap', compact('penginap'));
    }
    public function penginapcreate(Request $request)
    {
        // Validasi input (gunakan nama field yang konsisten dengan model Pelanggan)
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email:rfc,dns|max:255',
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
            'email' => $validated['email'] ?? null,
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
            'email' => 'nullable|email:rfc,dns|max:255',
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
            'email' => $validated['email'] ?? null,
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