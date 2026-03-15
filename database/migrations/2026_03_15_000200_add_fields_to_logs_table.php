<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logs')) {
            Schema::table('logs', function (Blueprint $table) {
                if (! Schema::hasColumn('logs', 'usuario_id')) {
                    $table->unsignedBigInteger('usuario_id')->nullable()->after('mensaje');
                }
                if (! Schema::hasColumn('logs', 'referencia_id')) {
                    $table->unsignedBigInteger('referencia_id')->nullable()->after('usuario_id');
                }
                if (! Schema::hasColumn('logs', 'referencia_tipo')) {
                    $table->string('referencia_tipo', 50)->nullable()->after('referencia_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('logs')) {
            Schema::table('logs', function (Blueprint $table) {
                if (Schema::hasColumn('logs', 'referencia_tipo')) {
                    $table->dropColumn('referencia_tipo');
                }
                if (Schema::hasColumn('logs', 'referencia_id')) {
                    $table->dropColumn('referencia_id');
                }
                if (Schema::hasColumn('logs', 'usuario_id')) {
                    $table->dropColumn('usuario_id');
                }
            });
        }
    }
};
