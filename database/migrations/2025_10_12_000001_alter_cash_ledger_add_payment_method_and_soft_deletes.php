<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_ledger', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_ledger', 'payment_method')) {
                $table->string('payment_method', 20)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('cash_ledger', 'meta')) {
                $table->json('meta')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('cash_ledger', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_ledger', function (Blueprint $table) {
            if (Schema::hasColumn('cash_ledger', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
            if (Schema::hasColumn('cash_ledger', 'meta')) {
                $table->dropColumn('meta');
            }
            if (Schema::hasColumn('cash_ledger', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
