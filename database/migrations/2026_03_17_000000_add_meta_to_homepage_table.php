<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('homepage') && ! Schema::hasColumn('homepage', 'meta')) {
            Schema::table('homepage', function (Blueprint $table) {
                $table->json('meta')->nullable()->after('nombre_empresa');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('homepage') && Schema::hasColumn('homepage', 'meta')) {
            Schema::table('homepage', function (Blueprint $table) {
                $table->dropColumn('meta');
            });
        }
    }
};
