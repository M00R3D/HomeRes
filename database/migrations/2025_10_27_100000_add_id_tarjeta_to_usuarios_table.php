<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (! Schema::hasColumn('usuarios', 'id_tarjeta')) {
                $table->unsignedBigInteger('id_tarjeta')->nullable()->after('area');
            }
        });

        Schema::table('usuarios', function (Blueprint $table) {
            try {
                $table->foreign('id_tarjeta')->references('id')->on('tarjetas_simuladas')->onDelete('set null');
            } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'id_tarjeta')) {
            Schema::table('usuarios', function (Blueprint $table) {
                try { $table->dropForeign(['id_tarjeta']); } catch (\Throwable $e) {}
                $table->dropColumn('id_tarjeta');
            });
        }
    }
};