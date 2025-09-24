<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Booking extends Model
{
    protected $table = 'booking';
    protected $fillable = [
        'pelanggan_id', 'kamar_id', 'tanggal_checkin', 'tanggal_checkout', 'jumlah_tamu', 'status','pemesanan', 'catatan', 'total_harga'
    ];
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }
    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'kamar_id');
    }
}
