<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->boolean('available')->default(false);
            $table->timestamps();
        });
        foreach (['BCG', 'Hepatitis B', 'Pentavalent (DTP-HepB-Hib)', 'Inactivated polio vaccine (IPV)', 'Pneumococcal conjugate vaccine (PCV)'] as $name) {
            DB::table('clinic_vaccines')->insert(['name' => $name, 'available' => false, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_vaccines');
    }
};
