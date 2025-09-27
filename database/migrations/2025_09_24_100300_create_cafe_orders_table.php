<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafe_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained('booking')->nullOnDelete();
            $table->decimal('total',12,2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cafe_orders');
    }
};