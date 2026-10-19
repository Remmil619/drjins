<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('barangay', 150)->nullable();
            $table->string('municipality', 150)->nullable();
            $table->string('province', 150)->nullable();
        });
    }
    public function down(): void {
        Schema::table('patients', fn (Blueprint $table) => $table->dropColumn(['barangay', 'municipality', 'province']));
    }
};
