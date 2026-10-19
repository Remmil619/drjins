<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_no')->unique();
            $table->string('guardian_name');
            $table->string('child_name');
            $table->string('contact_number');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('reason_for_visit')->nullable();
            $table->enum('status', [
                'Pending',
                'Approved',
                'Declined',
                'Completed',
                'Cancelled',
                'No Show',
                'Rescheduled',
            ])->default('Pending');
            $table->string('source')->default('online');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
