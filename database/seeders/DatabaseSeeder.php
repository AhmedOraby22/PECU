<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $defaultAdminPassword = (string) (env('DEFAULT_ADMIN_PASSWORD') ?: 'CHANGE_ME');
        $defaultAdminPhone = (string) (env('DEFAULT_ADMIN_PHONE') ?: '01000000000');

        AdminUser::firstOrCreate(
            ['username' => 'admin'],
            ['password' => $defaultAdminPassword]
        );

        User::firstOrCreate(
            ['email' => 'admin'],
            [
                'full_name' => 'مدير النظام',
                'phone' => $defaultAdminPhone,
                'password' => Hash::make($defaultAdminPassword),
                'role' => 'dashboard',
            ]
        );

        $defaults = [
            [
                'name' => 'أريكة كلاسيك لوكس',
                'name_en' => 'Classic Lux Sofa',
                'category' => 'living',
                'price' => 12500,
                'image' => '🛋️',
                'description' => 'أريكة فاخرة بتصميم كلاسيكي مع أقمشة مخملية عالية الجودة',
                'stock' => 15,
                'featured' => true,
            ],
            [
                'name' => 'طاولة طعام رويال',
                'name_en' => 'Royal Dining Table',
                'category' => 'dining',
                'price' => 8900,
                'image' => '🪑',
                'description' => 'طاولة طعام من خشب الزان الصلب بتشطيب ممتاز',
                'stock' => 8,
                'featured' => true,
            ],
            [
                'name' => 'سرير كينج مودرن',
                'name_en' => 'Modern King Bed',
                'category' => 'bedroom',
                'price' => 18000,
                'image' => '🛏️',
                'description' => 'سرير ملكي بتصميم عصري مع لوح رأس مبطن',
                'stock' => 6,
                'featured' => true,
            ],
            [
                'name' => 'مكتبة بيهايف',
                'name_en' => 'Beehive Bookshelf',
                'category' => 'office',
                'price' => 4500,
                'image' => '📚',
                'description' => 'مكتبة بتصميم عسلي فريد من خشب البلوط',
                'stock' => 20,
                'featured' => false,
            ],
        ];

        foreach ($defaults as $row) {
            Product::firstOrCreate(['name' => $row['name']], $row);
        }
    }
}
