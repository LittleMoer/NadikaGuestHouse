<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_order_id')->constrained('booking')->onDelete('cascade');
            $table->foreignId('kamar_id')->constrained('kamar')->onDelete('cascade');
            $table->integer('malam')->default(1); // jumlah malam kamar ini dipakai
            $table->decimal('harga_per_malam',12,2)->default(0);
            $table->decimal('subtotal',12,2)->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('booking_order_items');
    }
};
