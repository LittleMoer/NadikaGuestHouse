<?php

namespace App\Http\Controllers;
use App\Models\Booking;
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

        // Ambil kamar beserta booking yang overlap hari itu
        $kamar = Kamar::with(['bookings' => function($q) use ($start,$end){
            $q->where(function($qq) use ($start,$end){
                $qq->whereBetween('tanggal_checkin', [$start,$end])
                   ->orWhereBetween('tanggal_checkout', [$start,$end])
                   ->orWhere(function($qx) use ($start,$end){
                       $qx->where('tanggal_checkin','<=',$start)
                          ->where('tanggal_checkout','>=',$end);
                   });
            })->orderBy('tanggal_checkin');
        }, 'bookings.pelanggan'])->orderBy('tipe')->orderBy('nomor_kamar')->get();

        $rooms = $kamar->map(function($room){
            $active = $room->bookings->first();
            return [ 'room' => $room, 'activeBooking' => $active ];
        });

        $groupedKamar = $rooms->groupBy(fn($item) => $item['room']->tipe ?? 'Lain');

        $pelangganList = Pelanggan::orderBy('nama')->get();
        // Kamar tersedia = tidak punya booking aktif overlapping & status bukan 'terisi'
        $availableKamar = $kamar->filter(function($k) use ($rooms){
            $active = $k->bookings->first();
            return !$active || ($active->status >= 3); // 3=checkout,4=dibatalkan dianggap free
        })->values();

        return view('booking', compact('groupedKamar','tanggal','pelangganList','availableKamar'));
    }

    /**
     * Simpan booking baru (walk-in atau online)
     */
    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'pelanggan_id'      => 'required|exists:pelanggan,id',
            'kamar_id'          => 'required|exists:kamar,id',
            'tanggal_checkin'   => 'required|date|before:tanggal_checkout',
            'tanggal_checkout'  => 'required|date|after:tanggal_checkin',
            'jumlah_tamu'       => 'required|integer|min:1',
            'pemesanan'         => 'required|in:0,1', // 0 walk-in, 1 online
            'catatan'           => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return redirect()->route('booking.index')
                ->withErrors($validator, 'booking_create')
                ->withInput();
        }
        $data = $validator->validated();

        $kamar = Kamar::findOrFail($data['kamar_id']);

        // Cek overlapping booking untuk kamar yang sama (status aktif 1=dipesan,2=checkin)
        $overlap = Booking::where('kamar_id', $kamar->id)
            ->whereIn('status', [1,2])
            ->where(function($q) use ($data){
                $start = Carbon::parse($data['tanggal_checkin']);
                $end = Carbon::parse($data['tanggal_checkout']);
                $q->whereBetween('tanggal_checkin', [$start,$end])
                  ->orWhereBetween('tanggal_checkout', [$start,$end])
                  ->orWhere(function($qq) use ($start,$end){
                      $qq->where('tanggal_checkin','<=',$start)
                         ->where('tanggal_checkout','>=',$end);
                  });
            })->exists();
        if ($overlap) {
            return redirect()->route('booking.index')
                ->withErrors(['kamar_id' => 'Kamar sudah dibooking pada rentang waktu tersebut'], 'booking_create')
                ->withInput();
        }

        $start = Carbon::parse($data['tanggal_checkin']);
        $end = Carbon::parse($data['tanggal_checkout']);
        $days = max($start->diffInDays($end), 1);
        $total = $days * (int)$kamar->harga;

        // status awal: walk-in (pemesanan=0) langsung checkin (2); online (1) = dipesan (1)
        $status = $data['pemesanan'] == 0 ? 2 : 1;

        $booking = Booking::create([
            'pelanggan_id'      => $data['pelanggan_id'],
            'kamar_id'          => $kamar->id,
            'tanggal_checkin'   => $start,
            'tanggal_checkout'  => $end,
            'jumlah_tamu'       => $data['jumlah_tamu'],
            'status'            => $status,
            'pemesanan'         => (int)$data['pemesanan'],
            'catatan'           => $data['catatan'] ?? null,
            'total_harga'       => $total,
        ]);

        if ($status === 2) { // checkin
            $kamar->update(['status' => 'terisi']);
        }

        return redirect()->route('booking.index', ['tanggal' => $start->format('Y-m-d')])
            ->with('success', 'Booking berhasil dibuat.');
    }

    /**
     * Update status booking (checkin, checkout, cancel)
     */
    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::with('kamar')->findOrFail($id);
        $action = $request->get('action');
        $allowed = ['checkin','checkout','cancel'];
        if (!in_array($action, $allowed)) {
            return redirect()->back()->with('error', 'Aksi tidak dikenal');
        }
        switch ($action) {
            case 'checkin':
                if ($booking->status != 1) return redirect()->back()->with('error','Tidak dapat check-in');
                $booking->status = 2;
                $booking->kamar?->update(['status' => 'terisi']);
                break;
            case 'checkout':
                if ($booking->status != 2) return redirect()->back()->with('error','Tidak dapat check-out');
                $booking->status = 3;
                $booking->kamar?->update(['status' => 'tersedia']);
                break;
            case 'cancel':
                if (!in_array($booking->status, [1])) return redirect()->back()->with('error','Tidak dapat membatalkan');
                $booking->status = 4;
                $booking->kamar?->update(['status' => 'tersedia']);
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