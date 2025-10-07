<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use Carbon\Carbon;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama' => 'Andi Pratama',
                'telepon' => '081234567801',
                'alamat' => 'Jl. Melati No. 12, Bandung',
                'jenis_identitas' => 'KTP',
                'nomor_identitas' => '3201011234560001',
                'tempat_lahir' => 'Bandung',
                'tanggal_lahir' => '1990-04-12',
                'kewarganegaraan' => 'Indonesia',
            ],
            [
                'nama' => 'Budi Santoso',
                'telepon' => '081234567802',
                'alamat' => 'Jl. Kenanga No. 5, Jakarta',
                'jenis_identitas' => 'KTP',
                'nomor_identitas' => '3174019876540002',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1988-11-23',
                'kewarganegaraan' => 'Indonesia',
            ],
            [
                'nama' => 'Citra Lestari',
                'telepon' => '081234567803',
                'alamat' => 'Jl. Anggrek No. 8, Surabaya',
                'jenis_identitas' => 'KTP',
                'nomor_identitas' => '3578015647380003',
                'tempat_lahir' => 'Surabaya',
                'tanggal_lahir' => '1995-02-05',
                'kewarganegaraan' => 'Indonesia',
            ],
            [
                'nama' => 'Dewi Kartika',
                'telepon' => '081234567804',
                'alamat' => 'Jl. Cendana No. 3, Yogyakarta',
                'jenis_identitas' => 'SIM',
                'nomor_identitas' => 'YOG123456789',
                'tempat_lahir' => 'Yogyakarta',
                'tanggal_lahir' => '1993-07-18',
                'kewarganegaraan' => 'Indonesia',
            ],
            [
                'nama' => 'Eko Wijaya',
                'telepon' => '081234567805',
                'alamat' => 'Jl. Mawar No. 21, Medan',
                'jenis_identitas' => 'PASPOR',
                'nomor_identitas' => 'A12345678',
                'tempat_lahir' => 'Medan',
                'tanggal_lahir' => '1987-09-30',
                'kewarganegaraan' => 'Indonesia',
            ],
        ];
    }
}
