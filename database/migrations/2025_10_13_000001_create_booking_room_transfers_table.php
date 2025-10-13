<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_room_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('booking_order_item_id');
            $table->unsignedBigInteger('from_kamar_id')->nullable();
            $table->unsignedBigInteger('to_kamar_id');
            $table->enum('action', ['move','upgrade'])->default('move');
            $table->integer('old_price_per_malam')->nullable();
            $table->integer('new_price_per_malam')->nullable();
            $table->integer('old_total')->nullable();
            $table->integer('new_total')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('note', 190)->nullable();
            $table->timestamps();

            $table->index(['booking_id']);
            $table->index(['booking_order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_room_transfers');
    }
};
