<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('site_pages')) {
            Schema::create('site_pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('nav_label', 120);
                $table->string('title', 255);
                $table->text('summary')->nullable();
                $table->longText('body')->nullable();
                $table->string('image', 255)->default('');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        $defaults = [
            [
                'slug' => 'training',
                'nav_label' => 'التدريب',
                'title' => 'التدريب',
                'summary' => 'برامج تدريبية متخصصة تربط المعرفة الهندسية بالتطبيق العملي في مجال الأثاث والتصنيع.',
                'body' => 'تقدم الوحدة الخاصة لهندسة الأنتاج برامج تدريبية مصممة لتطوير المهارات الفنية والعملية في مجالات التصميم، التصنيع، مراقبة الجودة، وإدارة عمليات الإنتاج.',
                'sort_order' => 2,
            ],
            [
                'slug' => 'student-training',
                'nav_label' => 'تدريب الطلاب',
                'title' => 'تدريب الطلاب',
                'summary' => 'فرص تدريب عملي للطلاب داخل بيئة إنتاجية حقيقية تساعدهم على اكتساب خبرة تطبيقية.',
                'body' => 'نستقبل الطلاب في برامج تدريب ميداني تهدف إلى تعريفهم بدورة الإنتاج، استخدام المعدات، مبادئ السلامة، ومعايير الجودة داخل الورش والمعامل.',
                'sort_order' => 3,
            ],
            [
                'slug' => 'scientific-research',
                'nav_label' => 'البحث العلمي',
                'title' => 'البحث العلمي',
                'summary' => 'دعم البحوث التطبيقية والمشروعات العلمية المرتبطة بتطوير المنتجات وعمليات الإنتاج.',
                'body' => 'تدعم الوحدة أنشطة البحث العلمي من خلال توفير بيئة تطبيقية لاختبار الأفكار، تطوير النماذج، وتحويل المخرجات البحثية إلى حلول عملية قابلة للتنفيذ.',
                'sort_order' => 1,
            ],
            [
                'slug' => 'news',
                'nav_label' => 'أخبارنا',
                'title' => 'أخبارنا',
                'summary' => 'تابع أحدث أخبار الوحدة الخاصة لهندسة الأنتاج والفعاليات والأنشطة الجديدة.',
                'body' => 'هنا يمكنك نشر آخر الأخبار، الإعلانات، الفعاليات، والأنشطة الخاصة بالوحدة ليطلع عليها زوار الموقع بسهولة.',
                'sort_order' => 4,
            ],
        ];

        foreach ($defaults as $row) {
            DB::table('site_pages')->updateOrInsert(
                ['slug' => $row['slug']],
                array_merge($row, [
                    'active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_pages');
    }
};
