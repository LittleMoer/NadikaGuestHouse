<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        // [nama, telepon, alamat, jenis_identitas, nomor_identitas, tempat_lahir, tanggal_lahir, kewarganegaraan]
        $rows = [
            ['Andi Pratama', '081234567801', 'Jl. Melati No. 12, Bandung', 'KTP', '3201011234560001', 'Bandung', '1990-04-12', 'Indonesia'],
            ['Budi Santoso', '081234567802', 'Jl. Kenanga No. 5, Jakarta', 'KTP', '3174019876540002', 'Jakarta', '1988-11-23', 'Indonesia'],
            ['Citra Lestari', '081234567803', 'Jl. Anggrek No. 8, Surabaya', 'KTP', '3578015647380003', 'Surabaya', '1995-02-05', 'Indonesia'],
            ['Dewi Kartika', '081234567804', 'Jl. Cendana No. 3, Yogyakarta', 'SIM', 'YOG123456789', 'Yogyakarta', '1993-07-18', 'Indonesia'],
            ['Eko Wijaya', '081234567805', 'Jl. Mawar No. 21, Medan', 'PASPOR', 'A12345678', 'Medan', '1987-09-30', 'Indonesia'],
            ['Fajar Nugroho', '081234567806', 'Jl. Diponegoro No. 44, Semarang', 'KTP', '3374012233440006', 'Semarang', '1992-01-15', 'Indonesia'],
            ['Gita Maharani', '081234567807', 'Jl. Gajah Mada No. 9, Denpasar', 'KTP', '5171015566770007', 'Denpasar', '1997-06-02', 'Indonesia'],
            ['Hendra Gunawan', '081234567808', 'Jl. Sudirman No. 100, Pekanbaru', 'KTP', '1471017788990008', 'Pekanbaru', '1985-12-11', 'Indonesia'],
            ['Indah Permata', '081234567809', 'Jl. Pemuda No. 7, Malang', 'SIM', 'MLG987654321', 'Malang', '1999-03-27', 'Indonesia'],
            ['Joko Susilo', '081234567810', 'Jl. Ahmad Yani No. 55, Solo', 'KTP', '3372013344550010', 'Surakarta', '1980-08-08', 'Indonesia'],
            ['Kartika Sari', '081234567811', 'Jl. Pahlawan No. 2, Makassar', 'KTP', '7371014455660011', 'Makassar', '1994-10-19', 'Indonesia'],
            ['Lukman Hakim', '081234567812', 'Jl. Veteran No. 17, Palembang', 'KTP', '1671015566770012', 'Palembang', '1991-05-05', 'Indonesia'],
            ['Michael Brown', '+61412345678', '12 George St, Sydney', 'PASPOR', 'PA1234567', 'Sydney', '1986-02-14', 'Australia'],
            ['Sophie Müller', '+4915123456789', 'Hauptstraße 5, Berlin', 'PASPOR', 'C01X00T47', 'Berlin', '1996-09-09', 'Jerman'],
            ['Tanaka Hiroshi', '+819012345678', '1-2-3 Shibuya, Tokyo', 'PASPOR', 'TK7654321', 'Tokyo', '1983-04-01', 'Jepang'],
            // Data minimal (hanya nama & telepon) untuk menguji tampilan field kosong
            ['Nur Aisyah', '081234567816', null, null, null, null, null, null],
        ];

        foreach ($rows as $r) {
            Pelanggan::updateOrCreate(['nama' => $r[0]], [
                'telepon' => $r[1],
                'alamat' => $r[2],
                'jenis_identitas' => $r[3],
                'nomor_identitas' => $r[4],
                'tempat_lahir' => $r[5],
                'tanggal_lahir' => $r[6],
                'kewarganegaraan' => $r[7],
            ]);
        }
    }
}
