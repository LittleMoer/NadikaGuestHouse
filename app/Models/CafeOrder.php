<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CafeOrder extends Model
{
    protected $fillable = ['booking_id','total','catatan'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class,'booking_id');
    }
    public function items(): HasMany
    {
        return $this->hasMany(CafeOrderItem::class,'cafe_order_id');
    }
}
