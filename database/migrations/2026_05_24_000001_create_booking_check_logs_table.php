<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_check_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_order_id')->index();
            $table->string('type'); // 'checkin' or 'checkout'
            $table->dateTime('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_check_logs');
    }
};
