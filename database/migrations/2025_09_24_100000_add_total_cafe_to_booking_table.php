<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if(!Schema::hasColumn('booking','total_cafe')){
                $table->decimal('total_cafe',12,2)->default(0)->after('total_harga');
            }
        });
    }
    public function down(): void
    {
        Schema::table('booking', function (Blueprint $table) {
            if(Schema::hasColumn('booking','total_cafe')){
                $table->dropColumn('total_cafe');
            }
        });
    }
};