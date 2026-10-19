<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('patients', 'online_appointment_no')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->string('online_appointment_no')->nullable()->unique()->after('patient_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('patients', 'online_appointment_no')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn('online_appointment_no');
            });
        }
    }
};