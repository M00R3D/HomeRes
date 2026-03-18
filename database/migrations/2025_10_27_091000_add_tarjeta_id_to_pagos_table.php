<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            if (! Schema::hasColumn('pagos', 'tarjeta_id')) {
                $table->unsignedBigInteger('tarjeta_id')->nullable()->after('reservacion_id');
            }
        });

        Schema::table('pagos', function (Blueprint $table) {
            try {
                $table->foreign('tarjeta_id')->references('id')->on('tarjetas_simuladas')->onDelete('set null');
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('pagos', 'tarjeta_id')) {
            Schema::table('pagos', function (Blueprint $table) {
                try { $table->dropForeign(['tarjeta_id']); } catch (\Throwable $e) {}
                $table->dropColumn('tarjeta_id');
            });
        }
    }
};