<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Laravel notifications table
        if (Schema::hasTable('notifications') && ! Schema::hasColumn('notifications', 'link')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->string('link', 512)->nullable()->after('data');
            });
        }

        // legacy app notificaciones table
        if (Schema::hasTable('notificaciones') && ! Schema::hasColumn('notificaciones', 'link')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->string('link', 512)->nullable()->after('descripcion');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications') && Schema::hasColumn('notifications', 'link')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('link');
            });
        }

        if (Schema::hasTable('notificaciones') && Schema::hasColumn('notificaciones', 'link')) {
            Schema::table('notificaciones', function (Blueprint $table) {
                $table->dropColumn('link');
            });
        }
    }
};
