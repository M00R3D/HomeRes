<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pagos', 'usuario_id')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->unsignedBigInteger('usuario_id')->nullable()->after('tarjeta_id');
                try {
                    $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');
                } catch (\Throwable $e) {}
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pagos', 'usuario_id')) {
            Schema::table('pagos', function (Blueprint $table) {
                try { $table->dropForeign(['usuario_id']); } catch (\Throwable $e) {}
                $table->dropColumn('usuario_id');
            });
        }
    }
};
