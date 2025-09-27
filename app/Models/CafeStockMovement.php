<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CafeStockMovement extends Model
{
    protected $fillable = ['cafe_product_id','tipe','qty','keterangan','user_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(CafeProduct::class,'cafe_product_id');
    }
}
