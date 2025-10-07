<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Kamar extends Model
{
    protected $table = 'kamar';
    protected $fillable = [
        'nomor_kamar', 'tipe', 'kapasitas', 'harga', 'deskripsi'
    ];
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'kamar_id');
    }
}
