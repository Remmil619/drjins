<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_appointments', function (Blueprint $table) {
            $table->string('guardian_relationship')->nullable();
            $table->string('guardian_email')->nullable();
            $table->string('guardian_address', 500)->nullable();
            $table->date('child_date_of_birth')->nullable();
            $table->string('child_sex', 20)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->text('allergies')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('medical_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('online_appointments', function (Blueprint $table) {
            $table->dropColumn(['guardian_relationship', 'guardian_email', 'guardian_address', 'child_date_of_birth', 'child_sex', 'emergency_contact_name', 'emergency_contact_number', 'allergies', 'current_medications', 'medical_notes']);
        });
    }
};
