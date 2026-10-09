<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CafeProduct;
use App\Models\CafeStockMovement;
use Carbon\Carbon;

class CafeProductSeeder extends Seeder
{
    public function run(): void
    {
        if (CafeProduct::count() > 0) {
            $this->command?->info('CafeProductSeeder: sudah ada produk, lewati.');
            return;
        }

        $restockAt = Carbon::now()->subMonths(2)->startOfMonth()->setTime(9, 0);

        // [nama, kategori, satuan, harga_jual, stok_awal, minimal_stok, aktif]
        $rows = [
            ['Nasi Goreng Spesial', 'Makanan', 'porsi', 25000, 40, 10, true],
            ['Mie Goreng Jawa', 'Makanan', 'porsi', 22000, 40, 10, true],
            ['Indomie Rebus Telur', 'Makanan', 'porsi', 15000, 60, 15, true],
            ['Roti Bakar Coklat Keju', 'Makanan', 'porsi', 18000, 25, 5, true],
            ['Pisang Goreng', 'Snack', 'porsi', 12000, 30, 5, true],
            ['Kentang Goreng', 'Snack', 'porsi', 15000, 8, 10, true],      // stok di bawah minimal
            ['Kopi Tubruk', 'Minuman', 'gelas', 10000, 80, 20, true],
            ['Cappuccino', 'Minuman', 'gelas', 20000, 50, 10, true],
            ['Teh Manis Panas', 'Minuman', 'gelas', 6000, 100, 20, true],
            ['Es Jeruk', 'Minuman', 'gelas', 10000, 60, 15, true],
            ['Air Mineral 600ml', 'Minuman', 'botol', 5000, 4, 24, true],  // stok di bawah minimal
            ['Jus Alpukat', 'Minuman', 'gelas', 18000, 0, 5, false],       // produk nonaktif & habis
        ];

        foreach ($rows as $r) {
            $p = CafeProduct::create([
                'nama' => $r[0],
                'kategori' => $r[1],
                'satuan' => $r[2],
                'harga_jual' => $r[3],
                'stok' => $r[4],
                'minimal_stok' => $r[5],
                'aktif' => $r[6],
            ]);
            $p->forceFill(['created_at' => $restockAt, 'updated_at' => $restockAt])->saveQuietly();

            if ($r[4] > 0) {
                $m = CafeStockMovement::create([
                    'cafe_product_id' => $p->id,
                    'tipe' => 'in',
                    'qty' => $r[4],
                    'keterangan' => 'Stok awal',
                ]);
                $m->forceFill(['created_at' => $restockAt, 'updated_at' => $restockAt])->saveQuietly();
            }
        }

        // Contoh penyesuaian stok (stock opname)
        $kopi = CafeProduct::where('nama', 'Kopi Tubruk')->first();
        $opnameAt = Carbon::now()->subMonth()->startOfMonth()->setTime(8, 0);
        $m = CafeStockMovement::create([
            'cafe_product_id' => $kopi->id,
            'tipe' => 'adjust',
            'qty' => $kopi->stok,
            'keterangan' => 'Stock opname bulanan',
        ]);
        $m->forceFill(['created_at' => $opnameAt, 'updated_at' => $opnameAt])->saveQuietly();
    }
}
