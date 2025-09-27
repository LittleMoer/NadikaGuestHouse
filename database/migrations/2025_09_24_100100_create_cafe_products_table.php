<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cafe_products', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kategori')->nullable();
            $table->string('satuan',30)->default('porsi');
            $table->decimal('harga_jual',12,2)->default(0);
            $table->integer('stok')->default(0);
            $table->integer('minimal_stok')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cafe_products');
    }
};