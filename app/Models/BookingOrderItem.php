<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingOrderItem extends Model
{
    protected $table = 'booking_order_items';
    protected $fillable = [
        'booking_order_id','kamar_id','harga_per_malam','malam','subtotal'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class,'booking_order_id');
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class,'kamar_id');
    }
}
