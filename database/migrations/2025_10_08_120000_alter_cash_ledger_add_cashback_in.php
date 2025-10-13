<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // For MySQL enum alteration: modify column to include 'cashback_in'
        // Skip for sqlite (tests) since sqlite doesn't support MODIFY/ENUM
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE `cash_ledger` MODIFY `type` ENUM('dp_in','dp_remaining_in','dp_canceled','cafe_in','cashback_in') NOT NULL");
        }
    }
    public function down(): void
    {
        // Revert to previous set (drops cashback_in). Skip on sqlite.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'sqlite') {
            DB::statement("ALTER TABLE `cash_ledger` MODIFY `type` ENUM('dp_in','dp_remaining_in','dp_canceled','cafe_in') NOT NULL");
        }
    }
};
