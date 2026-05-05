<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('catalog_items')) {
            Schema::create('catalog_items', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('image', 255);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        DB::table('site_pages')->where('slug', 'news')->update([
            'nav_label' => 'الكتالوج',
            'title' => 'الكتالوج',
            'summary' => 'تصفح صور كتالوج الوحدة وأحدث الأعمال المتاحة للعرض.',
            'body' => 'يمكنك إضافة صور الكتالوج وأسمائها من لوحة التحكم لتظهر هنا مباشرة لزوار الموقع.',
            'updated_at' => now(),
        ]);

        DB::table('site_pages')->where('slug', 'design-your-furniture')->update([
            'nav_label' => 'صمم أثاثك الأن',
            'title' => 'صمم أثاثك الأن',
            'updated_at' => now(),
        ]);

        DB::table('site_pages')->where('slug', 'student-training')->update([
            'nav_label' => 'تدريبات الطلاب',
            'title' => 'تدريبات الطلاب',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('site_pages')->where('slug', 'news')->update([
            'nav_label' => 'أخبارنا',
            'title' => 'أخبارنا',
            'summary' => 'تابع أحدث أخبار الوحدة الخاصة لهندسة الأنتاج والفعاليات والأنشطة الجديدة.',
            'body' => 'هنا يمكنك نشر آخر الأخبار، الإعلانات، الفعاليات، والأنشطة الخاصة بالوحدة ليطلع عليها زوار الموقع بسهولة.',
            'updated_at' => now(),
        ]);

        DB::table('site_pages')->where('slug', 'design-your-furniture')->update([
            'nav_label' => 'صمم أثاثك',
            'title' => 'صمم أثاثك',
            'updated_at' => now(),
        ]);

        DB::table('site_pages')->where('slug', 'student-training')->update([
            'nav_label' => 'تدريب الطلاب',
            'title' => 'تدريب الطلاب',
            'updated_at' => now(),
        ]);

        Schema::dropIfExists('catalog_items');
    }
};
