<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('reservaciones', 'propiedad_id')) {
                $table->unsignedBigInteger('propiedad_id')->nullable()->after('usuario_id');
            }
        });
        DB::statement('UPDATE `reservaciones` SET `propiedad_id` = `cabana_id`');
        Schema::table('reservaciones', function (Blueprint $table) {
            try {
                $table->foreign('propiedad_id')->references('id')->on('propiedades')->onDelete('cascade');
            } catch (\Throwable $e) {}
        });
        Schema::table('reservaciones', function (Blueprint $table) {
            try {
                $table->dropForeign(['cabana_id']);
            } catch (\Throwable $e) {}
            if (Schema::hasColumn('reservaciones', 'cabana_id')) {
                $table->dropColumn('cabana_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('reservaciones', 'cabana_id')) {
                $table->unsignedBigInteger('cabana_id')->nullable()->after('usuario_id');
            }
        });
        DB::statement('UPDATE `reservaciones` SET `cabana_id` = `propiedad_id`');
        Schema::table('reservaciones', function (Blueprint $table) {
            try {
                $table->foreign('cabana_id')->references('id')->on('cabanas')->onDelete('cascade');
            } catch (\Throwable $e) {}
        });

        Schema::table('reservaciones', function (Blueprint $table) {
            try { $table->dropForeign(['propiedad_id']); } catch (\Throwable $e) {}
            if (Schema::hasColumn('reservaciones', 'propiedad_id')) {
                $table->dropColumn('propiedad_id');
            }
        });
    }
};