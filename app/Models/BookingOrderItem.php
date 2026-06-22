<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingOrderItem extends Model
{
    protected $table = 'booking_order_items';
    protected $fillable = [
        'booking_order_id', 'kamar_id', 'harga_per_malam', 'malam', 'subtotal', 'status', 'tanggal_checkin_actual', 'tanggal_checkout_actual'
    ];

    protected $casts = [
        'tanggal_checkin_actual' => 'datetime',
        'tanggal_checkout_actual' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BookingOrder::class,'booking_order_id');
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class,'kamar_id');
    }

    public function checkLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingCheckLog::class, 'booking_order_item_id');
    }
}
