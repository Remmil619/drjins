<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_appointments', function (Blueprint $table) {
            $table->enum('status', ['Pending', 'Approved', 'Declined', 'Completed', 'Cancelled', 'Rescheduled', 'No Show'])->default('Pending')->change();
        });
    }

    public function down(): void
    {
        // Keep the expanded status set to preserve recorded missed appointments.
    }
};
