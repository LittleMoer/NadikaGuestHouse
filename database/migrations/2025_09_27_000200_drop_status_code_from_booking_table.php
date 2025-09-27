<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('booking', 'status_code')) {
            try {
                Schema::table('booking', function (Blueprint $table) {
                    $table->dropColumn('status_code');
                });
            } catch (\Throwable $e) {
                // Fallback for environments without DBAL
                DB::statement('ALTER TABLE `booking` DROP COLUMN `status_code`');
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('booking', 'status_code')) {
            Schema::table('booking', function (Blueprint $table) {
                $table->string('status_code', 30)->nullable()->after('status');
            });
        }
    }
};
