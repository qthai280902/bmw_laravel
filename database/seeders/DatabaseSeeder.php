<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
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
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin BMW Showroom',
                'password' => Hash::make('12345678'),
            ]
        );

        $this->call([
            BrandSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
