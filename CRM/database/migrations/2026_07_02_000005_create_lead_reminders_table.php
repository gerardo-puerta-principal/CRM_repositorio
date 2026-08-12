<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->index();
            $table->text('description');
            $table->string('status', 20)->default('Pendiente')->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['lead_id', 'status', 'scheduled_at'], 'lead_reminders_lead_status_scheduled_idx');
            $table->index(['status', 'scheduled_at'], 'lead_reminders_status_scheduled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_reminders');
    }
};
