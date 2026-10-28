<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('guardian_first_name', 80)->nullable();
            $table->string('guardian_middle_name', 80)->nullable();
            $table->string('guardian_surname', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('patients', fn (Blueprint $table) => $table->dropColumn([
            'guardian_first_name', 'guardian_middle_name', 'guardian_surname',
        ]));
    }
};
