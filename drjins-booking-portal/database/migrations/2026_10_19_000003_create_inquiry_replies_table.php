<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_replies', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_reference');
            $table->string('sender', 20);
            $table->text('message');
            $table->timestamps();
            $table->foreign('inquiry_reference')->references('reference')->on('inquiries')->cascadeOnDelete();
            $table->index(['inquiry_reference', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_replies');
    }
};
