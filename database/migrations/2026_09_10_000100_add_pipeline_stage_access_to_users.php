<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pipeline_stage_access_mode', 20)
                ->default('all')
                ->after('is_active');
        });

        Schema::create('pipeline_stage_user', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('pipeline_stage_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->primary(['user_id', 'pipeline_stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stage_user');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('pipeline_stage_access_mode');
        });
    }
};
