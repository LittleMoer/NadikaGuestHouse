<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafe_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_order_id')->constrained('cafe_orders')->onDelete('cascade');
            $table->foreignId('cafe_product_id')->constrained('cafe_products')->onDelete('cascade');
            $table->integer('qty');
            $table->decimal('harga_satuan',12,2)->default(0);
            $table->decimal('subtotal',12,2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cafe_order_items');
    }
};