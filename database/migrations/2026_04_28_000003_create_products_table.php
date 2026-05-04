<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            return;
        }
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('name_en', 255)->default('');
            $table->string('category', 64);
            $table->decimal('price', 12, 2)->default(0);
            $table->string('image', 64)->default('');
            $table->text('description');
            $table->integer('stock')->default(0);
            $table->boolean('featured')->default(false);
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

