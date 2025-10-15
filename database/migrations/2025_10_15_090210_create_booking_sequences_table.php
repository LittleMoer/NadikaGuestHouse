<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_sequences', function (Blueprint $table) {
            $table->date('seq_date')->primary();
            $table->unsignedInteger('counter');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('booking_sequences');
    }
};
