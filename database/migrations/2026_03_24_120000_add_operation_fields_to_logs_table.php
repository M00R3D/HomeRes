<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('logs')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            if (!Schema::hasColumn('logs', 'accion')) {
                $table->string('accion', 80)->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('logs', 'modelo')) {
                $table->string('modelo', 80)->nullable()->after('accion');
            }
            if (!Schema::hasColumn('logs', 'target_id')) {
                $table->unsignedBigInteger('target_id')->nullable()->after('modelo');
            }
            if (!Schema::hasColumn('logs', 'status')) {
                $table->enum('status', ['success', 'error', 'warning', 'info'])->nullable()->after('target_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('logs')) {
            return;
        }

        Schema::table('logs', function (Blueprint $table) {
            if (Schema::hasColumn('logs', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('logs', 'target_id')) {
                $table->dropColumn('target_id');
            }
            if (Schema::hasColumn('logs', 'modelo')) {
                $table->dropColumn('modelo');
            }
            if (Schema::hasColumn('logs', 'accion')) {
                $table->dropColumn('accion');
            }
        });
    }
};
