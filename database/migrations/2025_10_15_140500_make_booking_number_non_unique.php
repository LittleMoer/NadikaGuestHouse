<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            // Drop UNIQUE constraint if exists, then add a normal index
            try {
                $table->dropUnique('booking_booking_number_unique');
            } catch (\Throwable $e) {
                // Fallback: some DBs may generate different index names; try generic drop
                try { $table->dropUnique(['booking_number']); } catch (\Throwable $e2) {}
            }
            // Add non-unique index for performance on lookups by booking_number
            try { $table->index('booking_number'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            // Remove normal index and restore UNIQUE
            try { $table->dropIndex(['booking_number']); } catch (\Throwable $e) {}
            try { $table->unique('booking_number'); } catch (\Throwable $e) {}
        });
    }
};
