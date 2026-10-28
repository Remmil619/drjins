<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->string('service_key')->nullable()->unique();
            $table->boolean('price_pending')->default(false);
        });
    }
    public function down(): void
    {
        Schema::table('charges', function (Blueprint $table) {
            $table->dropUnique(['service_key']);
            $table->dropColumn(['service_key', 'price_pending']);
        });
    }
};
