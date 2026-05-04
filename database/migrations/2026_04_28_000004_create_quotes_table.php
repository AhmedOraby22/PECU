<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quotes')) {
            return;
        }
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('organization', 255)->default('');
            $table->string('email', 255);
            $table->string('phone', 64);
            $table->string('category', 128)->default('');
            $table->string('product', 255)->default('');
            $table->integer('quantity')->default(0);
            $table->string('budget', 128)->default('');
            $table->text('specs');
            $table->text('notes');
            $table->string('file_name', 255)->default('');
            $table->string('status', 64)->default('جديد');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};

