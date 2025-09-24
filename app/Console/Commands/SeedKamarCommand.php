<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kamar;
use Illuminate\Support\Facades\DB;

class SeedKamarCommand extends Command
{
    protected $signature = 'kamar:ensure {total=17}';
    protected $description = 'Memastikan jumlah kamar mencapai total tertentu (default 17) dengan 3 jenis kamar';

    public function handle(): int
    {
        $target = (int)$this->argument('total');
        if ($target < 1) {
            $this->error('Total harus >= 1');
            return self::FAILURE;
        }

        // Definisi 3 jenis kamar dan harga default / kapasitas
        $jenis = [
            ['tipe' => 'Standard', 'kapasitas' => 2, 'harga' => 250000, 'deskripsi' => 'Kamar Standard'],
            ['tipe' => 'Deluxe',   'kapasitas' => 3, 'harga' => 400000, 'deskripsi' => 'Kamar Deluxe'],
            ['tipe' => 'Suite',    'kapasitas' => 4, 'harga' => 650000, 'deskripsi' => 'Kamar Suite'],
        ];

        $existing = Kamar::count();
        $this->info("Kamar saat ini: {$existing}");

        if ($existing >= $target) {
            $this->info('Tidak perlu menambah. Sudah memenuhi atau melebihi target.');
            return self::SUCCESS;
        }

        $toCreate = $target - $existing;
        $this->info("Menambah {$toCreate} kamar...");

        // Ambil nomor kamar yang sudah ada agar tidak duplikat
        $existingNumbers = Kamar::pluck('nomor_kamar')->toArray();

        $added = 0;
        $counter = 1;
        while ($added < $toCreate) {
            $nomor = str_pad($counter, 3, '0', STR_PAD_LEFT);
            if (!in_array($nomor, $existingNumbers)) {
                $spec = $jenis[$added % 3]; // rotasi jenis
                Kamar::create([
                    'nomor_kamar' => $nomor,
                    'tipe' => $spec['tipe'],
                    'kapasitas' => $spec['kapasitas'],
                    'harga' => $spec['harga'],
                    'status' => 1,
                    'deskripsi' => $spec['deskripsi'],
                ]);
                $this->line("+ Kamar {$nomor} ({$spec['tipe']}) ditambahkan");
                $added++;
            }
            $counter++;
            if ($counter > 999) { // safety
                $this->error('Nomor kamar melebihi batas 999');
                break;
            }
        }

        $this->info('Selesai. Total kamar sekarang: '.Kamar::count());
        return self::SUCCESS;
    }
}
