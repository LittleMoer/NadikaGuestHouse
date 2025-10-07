<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kamar;

class KamarSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nomor_kamar'=>'101','tipe'=>'Standard','kapasitas'=>2,'harga'=>150000,'deskripsi'=>'Standard Fan'],
            ['nomor_kamar'=>'102','tipe'=>'Standard','kapasitas'=>2,'harga'=>170000,'deskripsi'=>'Standard AC'],
            ['nomor_kamar'=>'201','tipe'=>'Deluxe','kapasitas'=>3,'harga'=>250000,'deskripsi'=>'Deluxe Queen'],
            ['nomor_kamar'=>'202','tipe'=>'Deluxe','kapasitas'=>3,'harga'=>260000,'deskripsi'=>'Deluxe Twin'],
            ['nomor_kamar'=>'301','tipe'=>'Suite','kapasitas'=>4,'harga'=>400000,'deskripsi'=>'Suite Family'],
        ];
        foreach($data as $row){
            Kamar::updateOrCreate(['nomor_kamar'=>$row['nomor_kamar']], $row);
        }
    }
}
