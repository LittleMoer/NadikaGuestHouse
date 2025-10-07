<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafe_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cafe_product_id')->index();
            $table->enum('tipe', ['in','out','adjust']);
            $table->integer('qty');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cafe_stock_movements');
    }
};
