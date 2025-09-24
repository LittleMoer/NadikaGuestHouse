<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use App\Models\Kamar;
use App\Models\Pelanggan;
use Carbon\Carbon;

class BookingOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan ada pelanggan & kamar
        if(Pelanggan::count() === 0 || Kamar::count() === 0){
            $this->command?->warn('Lewati BookingOrderSeeder: pelanggan atau kamar kosong.');
            return;
        }

        // Hindari duplikasi jika sudah pernah dijalankan
        if(BookingOrder::count() > 0){
            $this->command?->info('BookingOrderSeeder: sudah ada data booking, lewati.');
            return;
        }

        $pelangganIds = Pelanggan::pluck('id')->shuffle();
        $kamarAll = Kamar::orderBy('nomor_kamar')->get();

        // Helper hitung total
        $createOrder = function(array $kamarIds, Carbon $checkin, Carbon $checkout, int $pelangganId, int $status, int $pemesanan, string $catatan = null){
            $kamarList = Kamar::whereIn('id',$kamarIds)->get();
            $days = max($checkin->diffInDays($checkout),1);
            $total = 0;
            foreach($kamarList as $k){ $total += $days * (int)$k->harga; }
            $order = BookingOrder::create([
                'pelanggan_id' => $pelangganId,
                'tanggal_checkin' => $checkin,
                'tanggal_checkout' => $checkout,
                'jumlah_tamu_total' => rand(1,4),
                'status' => $status, // 1 dipesan,2 checkin
                'pemesanan' => $pemesanan,
                'catatan' => $catatan,
                'total_harga' => $total,
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
            return $order;
        };

        $now = Carbon::now()->startOfDay();

        // 1. Multi-kamar (3 kamar) status checkin sekarang
        $createOrder($kamarAll->take(3)->pluck('id')->all(), $now, (clone $now)->addDays(2), $pelangganIds[0], 2, 0, 'Group family stay');

        // 2. Single kamar dipesan (belum checkin) mulai besok
        $createOrder([$kamarAll->slice(3,1)->first()->id], (clone $now)->addDay(), (clone $now)->addDays(3), $pelangganIds[1], 1, 1, 'Online early booking');

        // 3. Multi-kamar (2) dipesan minggu depan
        $createOrder($kamarAll->slice(4,2)->pluck('id')->all(), (clone $now)->addDays(7), (clone $now)->addDays(10), $pelangganIds[2], 1, 1, 'Business delegation');

        // 4. Single kamar checkin kemarin (masih aktif)
        $createOrder([$kamarAll->slice(6,1)->first()->id], (clone $now)->subDay(), (clone $now)->addDay(), $pelangganIds[3], 2, 0, 'Walk-in extension');

        // 5. Booking besar (4 kamar) bulan depan (dipesan)
        $nextMonthStart = (clone $now)->addMonth()->startOfMonth();
        $createOrder($kamarAll->slice(7,4)->pluck('id')->all(), $nextMonthStart, (clone $nextMonthStart)->addDays(5), $pelangganIds[4] ?? $pelangganIds[0], 1, 1, 'Conference block');

        $this->command?->info('BookingOrderSeeder: contoh booking multi-kamar berhasil dibuat.');
    }
}
