<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('appointments', fn (Blueprint $table) => $table->enum('status', [
            'Pending', 'Approved', 'Declined', 'Completed', 'Cancelled', 'Rescheduled', 'No Show', 'In Queue',
        ])->default('Pending')->change());
    }
    public function down(): void {
        // Preserve queue statuses already received from the clinic.
    }
};
