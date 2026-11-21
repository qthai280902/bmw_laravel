<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Services\Cache\ShowroomCacheService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Đảm bảo luôn có tài khoản Admin để đăng nhập sau khi migrate:fresh,
        // Vì tính năng đăng ký (Registration) đã bị vô hiệu hóa.
        User::firstOrCreate(
            ['email' => 'admin@bmw.com'],
            [
                'name' => 'Admin BMW Showroom',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'quanly1@bmw.com'],
            [
                'name' => 'Nguyễn Quốc Thái',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        $topSuperAdmin = User::firstOrCreate(
            ['email' => 'thai@bmw.com'],
            [
                'name' => 'Nguyễn Quốc Thái - Quản Lý',
                'role' => User::ROLE_ADMIN,
                'is_super_admin' => true,
                'password' => Hash::make('12345678'),
                'email_verified_at' => now(),
            ]
        );

        if (! $topSuperAdmin->is_super_admin) {
            $topSuperAdmin->forceFill(['is_super_admin' => true])->saveQuietly();
        }

        // Đội ngũ Tư vấn Bán hàng (Sales) phục vụ điều phối Lead tự động
        foreach ([
            ['email' => 'sales.minh@bmw.com', 'name' => 'Trần Quang Minh'],
            ['email' => 'sales.linh@bmw.com', 'name' => 'Nguyễn Thùy Linh'],
            ['email' => 'sales.duc@bmw.com', 'name' => 'Lê Anh Đức'],
        ] as $salesSeed) {
            User::firstOrCreate(
                ['email' => $salesSeed['email']],
                [
                    'name' => $salesSeed['name'],
                    'role' => User::ROLE_SALES,
                    'password' => Hash::make('12345678'),
                    'email_verified_at' => now(),
                ]
            );
        }

        app(ShowroomCacheService::class)->flushSalesRoster();

        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            ProductImageExpansionSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
