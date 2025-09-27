<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking', function(Blueprint $table){
            if(!Schema::hasColumn('booking','status_code')){
                $table->string('status_code',30)->default('dp_walkin')->after('status');
            }
            if(!Schema::hasColumn('booking','dp_percentage')){
                $table->unsignedTinyInteger('dp_percentage')->nullable()->after('status_code');
            }
            $table->index('status_code');
        });
        // Attempt lightweight migration of existing rows (best-effort)
        try {
            \DB::statement("UPDATE booking SET status_code = CASE 
                WHEN status = 4 THEN 'dibatalkan' 
                ELSE CONCAT(COALESCE(payment_status,'dp'),'_', CASE 
                        WHEN pemesanan = 0 THEN 'walkin' 
                        WHEN pemesanan = 1 THEN 'traveloka' 
                        ELSE 'walkin' END) END
                WHERE status_code = 'dp_walkin'");
        } catch(\Throwable $e) {
            // ignore; admin can manually adjust
        }
    }
    public function down(): void
    {
        Schema::table('booking', function(Blueprint $table){
            if(Schema::hasColumn('booking','status_code')) $table->dropColumn('status_code');
            if(Schema::hasColumn('booking','dp_percentage')) $table->dropColumn('dp_percentage');
        });
    }
};
