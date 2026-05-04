<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_pages') && !Schema::hasColumn('site_pages', 'links')) {
            Schema::table('site_pages', function (Blueprint $table) {
                $table->text('links')->nullable()->after('image');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('site_pages') && Schema::hasColumn('site_pages', 'links')) {
            Schema::table('site_pages', function (Blueprint $table) {
                $table->dropColumn('links');
            });
        }
    }
};
