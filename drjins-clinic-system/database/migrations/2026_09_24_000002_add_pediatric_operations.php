<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_appointments', fn (Blueprint $t) => $t->foreignId('patient_id')->nullable()->constrained()->restrictOnDelete());
        DB::table('patients')->whereNotNull('online_appointment_no')->orderBy('id')->each(function ($patient) {
            DB::table('online_appointments')->where('appointment_no', $patient->online_appointment_no)->update([
                'patient_id' => $patient->id,
            ]);
        });
        Schema::create('queue_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('online_appointment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->date('visit_date')->index();
            $t->string('status')->default('waiting');
            $t->text('reason');
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->unique(['patient_id', 'visit_date']);
        });
        Schema::table('consultations', function (Blueprint $t) {
            $t->foreignId('queue_entry_id')->nullable()->constrained()->restrictOnDelete();
            $t->uuid('request_key')->nullable()->unique();
            $t->timestamp('follow_up_closed_at')->nullable();
            $t->string('follow_up_outcome')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason', 500)->nullable();
        });
        Schema::create('growth_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->date('measured_on')->index();
            $t->decimal('weight_kg', 6, 2);
            $t->decimal('height_cm', 6, 2);
            $t->decimal('head_cm', 5, 2)->nullable();
            $t->decimal('temperature_c', 4, 1)->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('immunizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('vaccine', 150);
            $t->string('dose', 50);
            $t->date('administered_on');
            $t->string('source', 30);
            $t->string('provider', 150);
            $t->string('lot_number', 100)->nullable();
            $t->date('expiry_date')->nullable();
            $t->date('next_due_on')->nullable()->index();
            $t->timestamp('reminder_closed_at')->nullable();
            $t->string('reminder_outcome')->nullable();
            $t->text('notes')->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('medical_alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('category', 30);
            $t->string('description', 500);
            $t->string('reaction', 500)->nullable();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('charges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('patient_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->date('charged_on')->index();
            $t->string('description', 255);
            $t->unsignedBigInteger('amount_centavos');
            $t->uuid('request_key')->unique();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('charge_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->date('paid_on')->index();
            $t->unsignedBigInteger('amount_centavos');
            $t->string('method', 30);
            $t->string('reference', 150)->nullable();
            $t->uuid('request_key')->unique();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action', 30);
            $t->string('record_type', 100);
            $t->unsignedBigInteger('record_id');
            $t->json('changed_fields');
            $t->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'payments', 'charges', 'medical_alerts', 'immunizations', 'growth_records'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('consultations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('queue_entry_id');
            $t->dropColumn(['request_key', 'follow_up_closed_at', 'follow_up_outcome', 'voided_at', 'void_reason']);
        });
        Schema::dropIfExists('queue_entries');
        Schema::table('online_appointments', fn (Blueprint $t) => $t->dropConstrainedForeignId('patient_id'));
    }
};
