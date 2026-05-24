<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingCheckLog extends Model
{
    use HasFactory;

    protected $table = 'booking_check_logs';

    protected $fillable = [
        'booking_order_id',
        'type',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function bookingOrder()
    {
        return $this->belongsTo(BookingOrder::class, 'booking_order_id');
    }
}
