<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultations', fn (Blueprint $table) => $table->string('workflow_hash', 64)->nullable());
    }
    public function down(): void
    {
        Schema::table('consultations', fn (Blueprint $table) => $table->dropColumn('workflow_hash'));
    }
};
