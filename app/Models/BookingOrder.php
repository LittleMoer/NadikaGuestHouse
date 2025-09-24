<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingOrder extends Model
{
    // Menggunakan tabel 'booking' sebagai header multi-kamar
    protected $table = 'booking';
    protected $fillable = [
        'pelanggan_id','tanggal_checkin','tanggal_checkout','status','pemesanan','catatan','total_harga','jumlah_tamu_total'
    ];

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class,'pelanggan_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingOrderItem::class,'booking_order_id');
    }
}
