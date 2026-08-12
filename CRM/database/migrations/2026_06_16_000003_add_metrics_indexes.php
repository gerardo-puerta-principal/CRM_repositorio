<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_logs', function (Blueprint $table) {
            $table->index(['action', 'user_id', 'created_at'], 'lead_logs_action_user_created_idx');
            $table->index(['action', 'result', 'created_at'], 'lead_logs_action_result_created_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index(['assigned_user_id', 'status'], 'leads_assigned_status_idx');
            $table->index(['assigned_user_id', 'last_contact_at'], 'leads_assigned_last_contact_idx');
            $table->index(['assigned_user_id', 'reminder_at'], 'leads_assigned_reminder_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lead_logs', function (Blueprint $table) {
            $table->dropIndex('lead_logs_action_user_created_idx');
            $table->dropIndex('lead_logs_action_result_created_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_assigned_status_idx');
            $table->dropIndex('leads_assigned_last_contact_idx');
            $table->dropIndex('leads_assigned_reminder_idx');
        });
    }
};
