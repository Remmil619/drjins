<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinic_vaccines', function (Blueprint $table) {
            $table->unsignedTinyInteger('dose_count')->default(1);
            $table->unsignedSmallInteger('dose_interval_months')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('clinic_vaccines', fn (Blueprint $table) => $table->dropColumn(['dose_count', 'dose_interval_months']));
    }
};
