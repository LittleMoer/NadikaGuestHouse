<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BookingCheckLog;
use App\Models\BookingOrder;
use App\Models\BookingOrderItem;
use App\Models\BookingRoomTransfer;
use App\Models\CafeOrder;
use App\Models\CafeOrderItem;
use App\Models\CafeProduct;
use App\Models\CafeStockMovement;
use App\Models\Kamar;
use App\Models\Pelanggan;
use App\Models\User;
use Carbon\Carbon;

/**
 * Data dummy booking untuk menguji semua fitur:
 * status 1-4, channel walk-in/traveloka/agent1/agent2, payment dp/lunas/dp_cancel,
 * metode cash/transfer/qris/card, diskon review/follow, cashback agen, biaya tambahan,
 * per-head, durasi 0.5 & 1.5 hari, multi-kamar, checkout per kamar, pindah/upgrade kamar,
 * nota gabungan (booking_number sama), dan pesanan cafe.
 *
 * Semua tanggal relatif terhadap hari ini sehingga dashboard selalu berisi data aktif.
 */
class BookingOrderSeeder extends Seeder
{
    private array $seq = [];

    public function run(): void
    {
        if(Pelanggan::count() === 0 || Kamar::count() === 0){
            $this->command?->warn('Lewati BookingOrderSeeder: pelanggan atau kamar kosong.');
            return;
        }
        if(BookingOrder::count() > 0){
            $this->command?->info('BookingOrderSeeder: sudah ada data booking, lewati.');
            return;
        }

        $g = Pelanggan::orderBy('id')->pluck('id')->values();
        $owner = User::where('role','owner')->value('id');
        $admin = User::where('role','admin')->value('id');

        // Field: guest, rooms, in (offset hari), dur (hari), status, ch (pemesanan), pay, method, dp,
        //        created (offset hari), opsional: review, follow, cashback, manual, per_head, tamu,
        //        biaya, catatan, cafe [[hari_ke, [[produk, qty], ...]]], transfer, item_status, nota_of, by
        $specs = [
            // ===== Riwayat (sudah checkout) =====
            ['key'=>'h1','guest'=>0,'rooms'=>['101'],'in'=>-58,'dur'=>2,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'cash','dp'=>'full','created'=>-58,'catatan'=>'Walk-in, bayar tunai'],
            ['key'=>'h2','guest'=>1,'rooms'=>['201','202'],'in'=>-55,'dur'=>3,'status'=>3,'ch'=>1,'pay'=>'lunas','method'=>'transfer','dp'=>null,'created'=>-70,'manual'=>1400000,'catatan'=>'Booking Traveloka 2 kamar'],
            ['key'=>'h3','guest'=>12,'rooms'=>['301'],'in'=>-50,'dur'=>4,'status'=>3,'ch'=>2,'pay'=>'lunas','method'=>'transfer','dp'=>500000,'created'=>-60,'cashback'=>10,'tamu'=>3,
                'cafe'=>[[0,[['Nasi Goreng Spesial',3],['Es Jeruk',3]]],[2,[['Cappuccino',2],['Pisang Goreng',1]]]]],
            ['key'=>'h4','guest'=>3,'rooms'=>['102'],'in'=>-45,'dur'=>1,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'qris','dp'=>'full','created'=>-45,'review'=>true,
                'cafe'=>[[0,[['Mie Goreng Jawa',1],['Teh Manis Panas',2]]]]],
            ['key'=>'h5','guest'=>5,'rooms'=>['203'],'in'=>-40,'dur'=>1.5,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'card','dp'=>'full','created'=>-40,'biaya'=>75000,'tamu'=>3,'catatan'=>'Extra bed 1'],
            ['key'=>'h6','guest'=>6,'rooms'=>['104'],'in'=>-35,'dur'=>2,'status'=>3,'ch'=>3,'pay'=>'lunas','method'=>'transfer','dp'=>200000,'created'=>-38,'cashback'=>15],
            ['key'=>'h7','guest'=>7,'rooms'=>['302'],'in'=>-30,'dur'=>0.5,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'cash','dp'=>'full','created'=>-30,'catatan'=>'Transit / short stay'],
            ['key'=>'h8','guest'=>13,'rooms'=>['204'],'in'=>-28,'dur'=>5,'status'=>3,'ch'=>1,'pay'=>'lunas','method'=>'transfer','dp'=>null,'created'=>-40,'manual'=>1250000,
                'cafe'=>[[1,[['Cappuccino',1],['Roti Bakar Coklat Keju',1]]],[3,[['Cappuccino',1],['Kentang Goreng',1]]]]],
            ['key'=>'h9','guest'=>8,'rooms'=>['101','102'],'in'=>-25,'dur'=>2,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'cash','dp'=>'full','created'=>-25,'review'=>true,'follow'=>true,'per_head'=>true,'tamu'=>5,
                'cafe'=>[[0,[['Indomie Rebus Telur',5],['Kopi Tubruk',3]]]]],
            ['key'=>'h10','guest'=>9,'rooms'=>['303'],'in'=>-20,'dur'=>3,'status'=>3,'ch'=>2,'pay'=>'lunas','method'=>'transfer','dp'=>300000,'created'=>-27,
                'transfer'=>['action'=>'upgrade','from'=>'203','note'=>'Upgrade ke Junior Suite atas permintaan tamu']],
            ['key'=>'h11','guest'=>10,'rooms'=>['201'],'in'=>-14,'dur'=>2,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'qris','dp'=>'full','created'=>-14,'by'=>'admin',
                'cafe'=>[[1,[['Nasi Goreng Spesial',2],['Air Mineral 600ml',4]]]]],
            ['key'=>'h12','guest'=>14,'rooms'=>['301'],'in'=>-10,'dur'=>3,'status'=>3,'ch'=>1,'pay'=>'lunas','method'=>'transfer','dp'=>null,'created'=>-21,'manual'=>1100000,
                'cafe'=>[[0,[['Kopi Tubruk',2]]],[1,[['Mie Goreng Jawa',2],['Es Jeruk',2]]]]],
            ['key'=>'h13','guest'=>2,'rooms'=>['102'],'in'=>-6,'dur'=>2,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'cash','dp'=>'full','created'=>-6,'by'=>'admin'],
            ['key'=>'h14','guest'=>4,'rooms'=>['202'],'in'=>-4,'dur'=>3,'status'=>3,'ch'=>0,'pay'=>'lunas','method'=>'transfer','dp'=>300000,'created'=>-4,
                'cafe'=>[[0,[['Pisang Goreng',2],['Teh Manis Panas',2]]],[2,[['Nasi Goreng Spesial',1]]]]],

            // ===== Dibatalkan =====
            ['key'=>'c1','guest'=>11,'rooms'=>['203'],'in'=>-18,'dur'=>2,'status'=>4,'ch'=>1,'pay'=>'dp_cancel','method'=>'transfer','dp'=>200000,'created'=>-25,'manual'=>450000,'catatan'=>'Batal: jadwal tamu berubah'],
            ['key'=>'c2','guest'=>15,'rooms'=>['104'],'in'=>5,'dur'=>2,'status'=>4,'ch'=>0,'pay'=>'dp_cancel','method'=>'cash','dp'=>100000,'created'=>-2,'by'=>'admin','catatan'=>'Batal oleh tamu'],

            // ===== Sedang menginap (check-in) =====
            ['key'=>'a1','guest'=>0,'rooms'=>['201','202','203'],'in'=>-1,'dur'=>3,'status'=>2,'ch'=>0,'pay'=>'lunas','method'=>'transfer','dp'=>'full','created'=>-1,'tamu'=>8,'catatan'=>'Rombongan keluarga (tamu langganan)',
                'cafe'=>[[0,[['Nasi Goreng Spesial',4],['Mie Goreng Jawa',4],['Es Jeruk',8]]]]],
            ['key'=>'a2','guest'=>12,'rooms'=>['102'],'in'=>0,'dur'=>3,'status'=>2,'ch'=>3,'pay'=>'lunas','method'=>'transfer','dp'=>'full','created'=>-7,'cashback'=>20,
                'transfer'=>['action'=>'move','from'=>'103','note'=>'AC kamar 103 bermasalah']],
            ['key'=>'a3','guest'=>1,'rooms'=>['301','302'],'in'=>-2,'dur'=>3,'status'=>2,'ch'=>0,'pay'=>'lunas','method'=>'cash','dp'=>'full','created'=>-2,'tamu'=>6,
                'item_status'=>['301'=>3],'catatan'=>'Kamar 301 checkout lebih awal',
                'cafe'=>[[1,[['Indomie Rebus Telur',3],['Kopi Tubruk',3]]]]],
            ['key'=>'a4','guest'=>6,'rooms'=>['303'],'in'=>0,'dur'=>1,'status'=>2,'ch'=>1,'pay'=>'lunas','method'=>'transfer','dp'=>null,'created'=>-5,'manual'=>330000,'by'=>'admin'],

            // ===== Akan datang (dipesan) =====
            ['key'=>'u1','guest'=>7,'rooms'=>['104'],'in'=>0,'dur'=>1,'status'=>1,'ch'=>0,'pay'=>'lunas','method'=>'cash','dp'=>'full','created'=>-1,'catatan'=>'Datang hari ini, sudah lunas (siap check-in)'],
            ['key'=>'u2','guest'=>5,'rooms'=>['101'],'in'=>0,'dur'=>2,'status'=>1,'ch'=>0,'pay'=>'dp','method'=>'cash','dp'=>100000,'created'=>-3,'catatan'=>'Datang hari ini, masih DP (check-in harus ditolak)'],
            ['key'=>'u3','guest'=>3,'rooms'=>['204'],'in'=>1,'dur'=>2,'status'=>1,'ch'=>1,'pay'=>'dp','method'=>'transfer','dp'=>250000,'created'=>-10,'manual'=>520000],
            ['key'=>'u4','guest'=>9,'rooms'=>['101','103'],'in'=>2,'dur'=>2,'status'=>1,'ch'=>0,'pay'=>'dp','method'=>'cash','dp'=>150000,'created'=>-1,'by'=>'admin'],
            ['key'=>'u5','guest'=>2,'rooms'=>['201'],'in'=>4,'dur'=>2,'status'=>1,'ch'=>2,'pay'=>'lunas','method'=>'transfer','dp'=>'full','created'=>-3,'cashback'=>10],
            ['key'=>'u6','guest'=>8,'rooms'=>['303'],'in'=>7,'dur'=>1,'status'=>1,'ch'=>0,'pay'=>'dp','method'=>null,'dp'=>null,'created'=>0,'catatan'=>'Belum bayar DP'],
            ['key'=>'u7','guest'=>14,'rooms'=>['302'],'in'=>10,'dur'=>4,'status'=>1,'ch'=>1,'pay'=>'dp','method'=>'transfer','dp'=>400000,'created'=>-12,'manual'=>1500000],
            ['key'=>'u8','guest'=>4,'rooms'=>['201','202','203','204'],'in'=>20,'dur'=>5,'status'=>1,'ch'=>3,'pay'=>'dp','method'=>'transfer','dp'=>2000000,'created'=>0,'tamu'=>12,'catatan'=>'Blok kamar acara kantor'],
            ['key'=>'u9','guest'=>4,'rooms'=>['301'],'in'=>20,'dur'=>5,'status'=>1,'ch'=>3,'pay'=>'dp','method'=>'transfer','dp'=>null,'created'=>0,'tamu'=>4,'nota_of'=>'u8','catatan'=>'Tambahan kamar, nota gabung dengan blok acara'],
            ['key'=>'u10','guest'=>10,'rooms'=>['102'],'in'=>30,'dur'=>2,'status'=>1,'ch'=>0,'pay'=>'dp','method'=>'qris','dp'=>100000,'created'=>0],
        ];

        $today = Carbon::today();
        $kamar = Kamar::all()->keyBy('nomor_kamar');
        $products = CafeProduct::all()->keyBy('nama');
        $numbers = [];

        // Urutkan berdasarkan tanggal dibuat agar nomor nota berurutan per bulan
        usort($specs, fn($a, $b) => [$a['created'], $a['in']] <=> [$b['created'], $b['in']]);

        foreach ($specs as $s) {
            $dur = (float)$s['dur'];
            $start = $today->copy()->addDays($s['in'])->setTime($dur == 0.5 ? 6 : 12, 0);
            $end = $dur == 0.5 ? $start->copy()->setTime(12, 0) : $start->copy()->addMinutes((int)round($dur * 1440));
            $createdAt = $today->copy()->addDays($s['created'])->setTime(rand(8, 11), rand(0, 59));
            if ($createdAt->gt(now())) { $createdAt = now()->subMinutes(rand(5, 60)); }

            // Hitung harga mengikuti BookingController@store
            $rooms = collect($s['rooms'])->map(fn($n) => $kamar[$n]);
            $tamu = $s['tamu'] ?? min(2, $rooms->sum('kapasitas'));
            $base = $rooms->sum(fn($k) => (int)$k->harga * $dur);
            if (!empty($s['per_head']) && $tamu > 2) { $base += 50000 * ($tamu - 2); }
            $base = (int)max($base, 100000);
            $after = $base;
            if (!empty($s['review'])) { $after = (int)round($after * 0.9); }
            if (!empty($s['follow'])) { $after = (int)round($after * 0.9); }
            $diskon = $base - $after;
            $total = $after;
            if ($s['ch'] === 1) { $total = (int)$s['manual']; }
            $cbPct = $s['cashback'] ?? 0;
            $cbAmount = 0;
            if (in_array($s['ch'], [2, 3], true) && $cbPct) {
                $cbAmount = (int)round($total * $cbPct / 100);
                $total -= $cbAmount;
                $diskon += $cbAmount;
            }
            $biaya = (int)($s['biaya'] ?? 0);
            $dp = $s['dp'] === 'full' ? $total + $biaya : $s['dp'];

            $catatan = $s['catatan'] ?? null;
            if ($cbAmount > 0) {
                $catatan = trim(($catatan ? $catatan."\n" : '').'[Cashback Agen (potongan): '.$cbPct.'% = Rp'.number_format($cbAmount, 0, ',', '.').']');
            }

            $bookingNumber = isset($s['nota_of']) ? $numbers[$s['nota_of']] : $this->nextNumber($createdAt);
            $numbers[$s['key']] = $bookingNumber;

            $order = BookingOrder::withoutEvents(fn() => BookingOrder::create([
                'pelanggan_id' => $g[$s['guest']],
                'tanggal_checkin' => $start,
                'tanggal_checkout' => $end,
                'jumlah_tamu_total' => $tamu,
                'status' => $s['status'],
                'pemesanan' => $s['ch'],
                'catatan' => $catatan,
                'total_harga' => $total,
                'payment_status' => $s['pay'],
                'payment_method' => $s['method'],
                'dp_amount' => $dp ?: null,
                'discount_review' => !empty($s['review']),
                'discount_follow' => !empty($s['follow']),
                'extra_time' => 'none',
                'per_head_mode' => !empty($s['per_head']),
                'diskon' => $diskon,
                'biaya_tambahan' => $biaya,
                'booking_number' => $bookingNumber,
                'created_by' => ($s['by'] ?? 'owner') === 'admin' ? $admin : $owner,
            ]));
            $order->forceFill(['created_at' => $createdAt, 'updated_at' => max($createdAt, min(now(), $end))])->saveQuietly();

            foreach ($rooms as $k) {
                $itemStatus = $s['item_status'][$k->nomor_kamar] ?? $s['status'];
                $checkinAt = in_array($itemStatus, [2, 3]) ? $start->copy()->addMinutes(rand(0, 90)) : null;
                $checkoutAt = null;
                if ($itemStatus === 3) {
                    $checkoutAt = $s['status'] === 3 ? $end->copy()->subMinutes(rand(10, 60)) : now()->subHours(1);
                }
                $item = BookingOrderItem::create([
                    'booking_order_id' => $order->id,
                    'kamar_id' => $k->id,
                    'malam' => (int)max(ceil($dur), 1),
                    'harga_per_malam' => (int)$k->harga,
                    'subtotal' => (int)($k->harga * $dur),
                    'status' => $itemStatus,
                    'tanggal_checkin_actual' => $checkinAt,
                    'tanggal_checkout_actual' => $checkoutAt,
                ]);
                $item->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->saveQuietly();

                foreach (['checkin' => $checkinAt, 'checkout' => $checkoutAt] as $type => $at) {
                    if ($at) {
                        BookingCheckLog::create([
                            'booking_order_id' => $order->id,
                            'booking_order_item_id' => $item->id,
                            'type' => $type,
                            'recorded_at' => $at,
                        ]);
                    }
                }

                if (!empty($s['transfer'])) {
                    $t = $s['transfer'];
                    $from = $kamar[$t['from']];
                    $nights = (int)max(ceil($dur), 1);
                    $tr = BookingRoomTransfer::create([
                        'booking_id' => $order->id,
                        'booking_order_item_id' => $item->id,
                        'from_kamar_id' => $from->id,
                        'to_kamar_id' => $k->id,
                        'action' => $t['action'],
                        'old_price_per_malam' => (int)$from->harga,
                        'new_price_per_malam' => (int)$k->harga,
                        'old_total' => (int)$from->harga * $nights,
                        'new_total' => (int)$k->harga * $nights,
                        'actor_user_id' => $owner,
                        'note' => $t['note'],
                    ]);
                    $at = min(now(), $start->copy()->addHours(3));
                    $tr->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
                }
            }

            // Pesanan cafe (hanya untuk tamu yang pernah check-in)
            $cafeTotal = 0;
            foreach ($s['cafe'] ?? [] as [$dayOffset, $lines]) {
                $at = $start->copy()->addDays($dayOffset)->setTime(19, rand(0, 59));
                if ($at->gt(now())) { $at = now()->subMinutes(rand(10, 120)); }
                $co = CafeOrder::create(['booking_id' => $order->id, 'total' => 0]);
                $orderTotal = 0;
                foreach ($lines as [$name, $qty]) {
                    $p = $products[$name];
                    $sub = $qty * (int)$p->harga_jual;
                    $orderTotal += $sub;
                    $ci = CafeOrderItem::create([
                        'cafe_order_id' => $co->id,
                        'cafe_product_id' => $p->id,
                        'qty' => $qty,
                        'harga_satuan' => $p->harga_jual,
                        'subtotal' => $sub,
                    ]);
                    $ci->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
                    $p->stok -= $qty;
                    $p->save();
                    $m = CafeStockMovement::create([
                        'cafe_product_id' => $p->id,
                        'tipe' => 'out',
                        'qty' => $qty,
                        'keterangan' => 'Order booking #'.$order->id,
                    ]);
                    $m->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
                }
                $co->forceFill(['total' => $orderTotal, 'created_at' => $at, 'updated_at' => $at])->saveQuietly();
                $cafeTotal += $orderTotal;
            }
            if ($cafeTotal > 0) {
                $order->forceFill(['total_cafe' => $cafeTotal])->saveQuietly();
            }
        }

        $this->command?->info('BookingOrderSeeder: '.count($specs).' booking dummy berhasil dibuat.');
    }

    /** Nomor nota format ym### per bulan (sama seperti BookingController@store). */
    private function nextNumber(Carbon $at): string
    {
        $prefix = $at->format('ym');
        $this->seq[$prefix] = ($this->seq[$prefix] ?? 0) + 1;
        return $prefix.str_pad((string)$this->seq[$prefix], 3, '0', STR_PAD_LEFT);
    }
}
