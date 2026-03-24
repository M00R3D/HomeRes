<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            try {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_notifiable_read_idx');
            } catch (\Throwable $e) {
                // ignore if already exists
            }

            try {
                $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_notifiable_created_idx');
            } catch (\Throwable $e) {
                // ignore if already exists
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            try {
                $table->dropIndex('notifications_notifiable_read_idx');
            } catch (\Throwable $e) {
            }

            try {
                $table->dropIndex('notifications_notifiable_created_idx');
            } catch (\Throwable $e) {
            }
        });
    }
};
