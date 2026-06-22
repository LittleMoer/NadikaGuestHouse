<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_order_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(1)->index()->after('subtotal');
            $table->dateTime('tanggal_checkin_actual')->nullable()->after('status');
            $table->dateTime('tanggal_checkout_actual')->nullable()->after('tanggal_checkin_actual');
        });

        Schema::table('booking_check_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_order_item_id')->nullable()->index()->after('booking_order_id');
        });

        // Backfill existing data
        $bookings = DB::table('booking')->get();
        foreach ($bookings as $booking) {
            $checkinLog = DB::table('booking_check_logs')
                ->where('booking_order_id', $booking->id)
                ->where('type', 'checkin')
                ->orderBy('recorded_at', 'desc')
                ->first();

            $checkoutLog = DB::table('booking_check_logs')
                ->where('booking_order_id', $booking->id)
                ->where('type', 'checkout')
                ->orderBy('recorded_at', 'desc')
                ->first();

            DB::table('booking_order_items')
                ->where('booking_order_id', $booking->id)
                ->update([
                    'status' => $booking->status,
                    'tanggal_checkin_actual' => $checkinLog ? $checkinLog->recorded_at : null,
                    'tanggal_checkout_actual' => $checkoutLog ? $checkoutLog->recorded_at : null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_check_logs', function (Blueprint $table) {
            $table->dropColumn('booking_order_item_id');
        });

        Schema::table('booking_order_items', function (Blueprint $table) {
            $table->dropColumn(['status', 'tanggal_checkin_actual', 'tanggal_checkout_actual']);
        });
    }
};
