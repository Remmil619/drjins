<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', fn (Blueprint $table) => $table->unsignedBigInteger('patient_id')->nullable()->change());
        Schema::table('online_appointments', fn (Blueprint $table) => $table->enum('status', [
            'Pending', 'Approved', 'Declined', 'Completed', 'Cancelled', 'Rescheduled', 'No Show', 'In Queue',
        ])->default('Pending')->change());
    }

    public function down(): void
    {
        // Keep online-only queue entries intact when rolling back application code.
    }
};
