<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. High-volume leads table indexes
        Schema::table('leads', function (Blueprint $table): void {
            // Default ordering index: WHERE deleted_at IS NULL ORDER BY created_at DESC, id DESC
            $table->index(['deleted_at', 'created_at', 'id'], 'leads_del_created_id_idx');
            // Stage/Status ordered index: WHERE deleted_at IS NULL AND lead_status_id = ? ORDER BY created_at DESC
            $table->index(['deleted_at', 'lead_status_id', 'created_at'], 'leads_del_status_created_idx');
            // Created-by attribution index in scopeAccessibleTo()
            $table->index(['deleted_at', 'created_by_user_id'], 'leads_del_creator_idx');
            // Assigned-user ordering index: WHERE deleted_at IS NULL AND assigned_user_id = ? ORDER BY created_at DESC
            $table->index(['deleted_at', 'assigned_user_id', 'created_at'], 'leads_del_assigned_created_idx');
            // Global next follow up index across all stages
            $table->index(['deleted_at', 'next_follow_up_at'], 'leads_del_followup_idx');
            // Source dropdown distinct & filter index
            $table->index(['deleted_at', 'source'], 'leads_del_source_idx');
        });

        // 2. High-volume lead_followups table indexes
        Schema::table('lead_followups', function (Blueprint $table): void {
            // User followup history & reports date range: WHERE user_id = ? AND followed_up_at BETWEEN ? AND ?
            $table->index(['user_id', 'followed_up_at'], 'lf_user_followed_idx');
            // Status transition history: WHERE to_status_id = ? AND followed_up_at BETWEEN ? AND ?
            $table->index(['to_status_id', 'followed_up_at'], 'lf_to_status_followed_idx');
            // Callback index
            $table->index(['next_follow_up_at'], 'lf_next_followup_idx');
        });

        // 3. High-volume lead_status_histories table indexes
        Schema::table('lead_status_histories', function (Blueprint $table): void {
            $table->index(['to_status_id', 'changed_at'], 'lsh_to_status_changed_idx');
            $table->index(['from_status_id', 'changed_at'], 'lsh_from_status_changed_idx');
            $table->index(['changed_by_user_id', 'changed_at'], 'lsh_user_changed_idx');
        });

        // 4. Calendar events table indexes
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->index(['deleted_at', 'user_id', 'start_time', 'end_time'], 'cal_del_user_time_idx');
            $table->index(['deleted_at', 'start_time', 'end_time'], 'cal_del_time_idx');
            $table->index(['deleted_at', 'status', 'start_time'], 'cal_del_status_start_idx');
        });

        // 5. Campaign lead pivot reverse index for lead-to-campaign joins
        Schema::table('campaign_lead', function (Blueprint $table): void {
            $table->index(['lead_id', 'campaign_id'], 'campaign_lead_reverse_idx');
        });

        // 6. Notifications index for user notification drawer
        Schema::table('notifications', function (Blueprint $table): void {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'notif_user_read_created_idx');
        });

        // 7. Lead stage field values lookup indexes
        Schema::table('lead_stage_field_values', function (Blueprint $table): void {
            $table->index(['lead_id', 'field_key'], 'lsfv_lead_field_key_idx');
            $table->index(['lead_id', 'pipeline_stage_id', 'created_at', 'id'], 'lsfv_lead_stage_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('lead_stage_field_values', function (Blueprint $table): void {
            $table->dropIndex('lsfv_lead_field_key_idx');
            $table->dropIndex('lsfv_lead_stage_created_idx');
        });

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropIndex('notif_user_read_created_idx');
        });

        Schema::table('campaign_lead', function (Blueprint $table): void {
            $table->dropIndex('campaign_lead_reverse_idx');
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('cal_del_user_time_idx');
            $table->dropIndex('cal_del_time_idx');
            $table->dropIndex('cal_del_status_start_idx');
        });

        Schema::table('lead_status_histories', function (Blueprint $table): void {
            $table->dropIndex('lsh_to_status_changed_idx');
            $table->dropIndex('lsh_from_status_changed_idx');
            $table->dropIndex('lsh_user_changed_idx');
        });

        Schema::table('lead_followups', function (Blueprint $table): void {
            $table->dropIndex('lf_user_followed_idx');
            $table->dropIndex('lf_to_status_followed_idx');
            $table->dropIndex('lf_next_followup_idx');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_del_created_id_idx');
            $table->dropIndex('leads_del_status_created_idx');
            $table->dropIndex('leads_del_creator_idx');
            $table->dropIndex('leads_del_assigned_created_idx');
            $table->dropIndex('leads_del_followup_idx');
            $table->dropIndex('leads_del_source_idx');
        });
    }
};
