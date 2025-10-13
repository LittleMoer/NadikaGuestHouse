<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingRoomTransfer extends Model
{
    protected $table = 'booking_room_transfers';

    protected $fillable = [
        'booking_id',
        'booking_order_item_id',
        'from_kamar_id',
        'to_kamar_id',
        'action', // move|upgrade
        'old_price_per_malam',
        'new_price_per_malam',
        'old_total',
        'new_total',
        'actor_user_id',
        'note',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class, 'booking_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(BookingOrderItem::class, 'booking_order_item_id');
    }

    public function fromKamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class, 'from_kamar_id');
    }

    public function toKamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class, 'to_kamar_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
