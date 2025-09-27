<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafe_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cafe_product_id')->constrained('cafe_products')->onDelete('cascade');
            $table->enum('tipe',['in','out','adjust']);
            $table->integer('qty');
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cafe_stock_movements');
    }
};