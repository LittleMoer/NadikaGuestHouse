<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if(!Schema::hasColumn('booking','payment_status')){
                $table->enum('payment_status',[ 'dp','lunas' ])->default('dp')->after('status');
            }
        });
    }
    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if(Schema::hasColumn('booking','payment_status')){
                $table->dropColumn('payment_status');
            }
        });
    }
};