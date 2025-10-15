<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if (!Schema::hasColumn('booking', 'booking_number')) {
                $table->string('booking_number', 32)->nullable()->unique()->after('biaya_tambahan');
            }
        });
    }
    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if (Schema::hasColumn('booking', 'booking_number')) {
                $table->dropUnique(['booking_number']);
                $table->dropColumn('booking_number');
            }
        });
    }
};
