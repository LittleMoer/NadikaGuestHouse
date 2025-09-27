<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CafeProduct extends Model
{
    protected $fillable = ['nama','kategori','satuan','harga_jual','stok','minimal_stok','aktif'];

    public function stockMovements(): HasMany
    {
        return $this->hasMany(CafeStockMovement::class,'cafe_product_id');
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(CafeOrderItem::class,'cafe_product_id');
    }
}
