<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CafeOrderItem extends Model
{
    protected $fillable = ['cafe_order_id','cafe_product_id','qty','harga_satuan','subtotal'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CafeOrder::class,'cafe_order_id');
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(CafeProduct::class,'cafe_product_id');
    }
}
