<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->index(['deleted_at', 'lead_status_id', 'updated_at'], 'leads_status_updated_idx');
            $table->index(['deleted_at', 'lead_status_id', 'next_follow_up_at'], 'leads_status_followup_idx');
            $table->index(['deleted_at', 'assigned_user_id', 'lead_status_id'], 'leads_assigned_status_idx');
        });

        Schema::table('lead_status_histories', function (Blueprint $table): void {
            $table->index(['lead_id', 'to_status_id', 'from_status_id'], 'lsh_lead_status_composite_idx');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_status_updated_idx');
            $table->dropIndex('leads_status_followup_idx');
            $table->dropIndex('leads_assigned_status_idx');
        });

        Schema::table('lead_status_histories', function (Blueprint $table): void {
            $table->dropIndex('lsh_lead_status_composite_idx');
        });
    }
};
