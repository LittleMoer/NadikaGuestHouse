<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafe_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cafe_order_id')->index();
            $table->unsignedBigInteger('cafe_product_id')->index();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedBigInteger('harga_satuan')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cafe_order_items');
    }
};
