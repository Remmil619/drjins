<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('guardian_address', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Retain the wider column to avoid truncating existing addresses.
    }
};
