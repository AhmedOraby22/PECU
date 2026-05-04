<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hero_slides')) {
            Schema::create('hero_slides', function (Blueprint $table) {
                $table->id();
                $table->string('image', 255);
                $table->string('title', 255)->default('');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
