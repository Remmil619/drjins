<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (!Schema::hasColumn('patients', 'height')) {
                $table->string('height')->nullable()->after('sex');
            }

            if (!Schema::hasColumn('patients', 'weight')) {
                $table->string('weight')->nullable()->after('height');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (Schema::hasColumn('patients', 'height')) {
                $table->dropColumn('height');
            }

            if (Schema::hasColumn('patients', 'weight')) {
                $table->dropColumn('weight');
            }
        });
    }
};