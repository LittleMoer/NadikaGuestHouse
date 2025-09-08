<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'nama',
        'email',
        'telepon',
        'alamat',
        'jenis_identitas',
        'nomor_identitas',
        'tempat_lahir',
        'tanggal_lahir',
        'kewarganegaraan',
    ];
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'pelanggan_id');
    }
}