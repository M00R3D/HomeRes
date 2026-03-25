<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::table('notification_preferences', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_preferences', 'resource_link_types')) {
                $table->json('resource_link_types')->nullable()->after('categories');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_preferences') || ! Schema::hasColumn('notification_preferences', 'resource_link_types')) {
            return;
        }

        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn('resource_link_types');
        });
    }
};