<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_order_id')->index();
            $table->unsignedBigInteger('kamar_id')->nullable()->index();
            $table->unsignedBigInteger('harga_per_malam')->default(0);
            $table->unsignedInteger('malam')->default(1);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('booking_order_items');
    }
};
