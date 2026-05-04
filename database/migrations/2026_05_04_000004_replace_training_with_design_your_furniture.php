<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_pages')->where('slug', 'training')->delete();

        DB::table('site_pages')->updateOrInsert(
            ['slug' => 'design-your-furniture'],
            [
                'nav_label' => 'صمم أثاثك',
                'title' => 'صمم أثاثك',
                'summary' => 'شاركنا فكرتك أو مقاساتك وسنساعدك في تحويلها إلى تصميم أثاث مناسب لاحتياجك.',
                'body' => 'يمكنك إضافة رابط نموذج أو صفحة خارجية تتيح للزائر إرسال تفاصيل تصميمه، المقاسات، الصور المرجعية، وأي ملاحظات خاصة ليتم التواصل معه.',
                'image' => '',
                'links' => json_encode([], JSON_UNESCAPED_UNICODE),
                'sort_order' => 2,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('site_pages')->where('slug', 'design-your-furniture')->delete();

        DB::table('site_pages')->updateOrInsert(
            ['slug' => 'training'],
            [
                'nav_label' => 'التدريب',
                'title' => 'التدريب',
                'summary' => 'برامج تدريبية متخصصة تربط المعرفة الهندسية بالتطبيق العملي في مجال الأثاث والتصنيع.',
                'body' => 'تقدم الوحدة الخاصة لهندسة الأنتاج برامج تدريبية مصممة لتطوير المهارات الفنية والعملية في مجالات التصميم، التصنيع، مراقبة الجودة، وإدارة عمليات الإنتاج.',
                'image' => '',
                'links' => json_encode([], JSON_UNESCAPED_UNICODE),
                'sort_order' => 2,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
};
