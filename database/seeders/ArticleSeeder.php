<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@bmw.com')->first();

        $articles = [
            [
                'title' => 'Ưu đãi chăm sóc BMW mùa hè',
                'category' => Article::CATEGORY_OFFERS,
                'excerpt' => 'Gói chăm sóc và kiểm tra xe BMW chính hãng dành cho khách hàng showroom trong mùa hè.',
                'body' => "Showroom BMW giới thiệu chương trình ưu đãi chăm sóc xe mùa hè với quy trình kiểm tra tổng quát, tư vấn bảo dưỡng và hỗ trợ đặt lịch nhanh.\n\nKhách hàng có thể liên hệ cố vấn dịch vụ để chọn khung giờ phù hợp và nhận đề xuất chăm sóc theo tình trạng xe hiện tại.",
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Trải nghiệm lái thử BMW cuối tuần',
                'category' => Article::CATEGORY_SHOWROOM_EVENTS,
                'excerpt' => 'Sự kiện lái thử cuối tuần giúp khách hàng trải nghiệm các dòng BMW mới trong không gian showroom.',
                'body' => "Cuối tuần này, showroom tổ chức chương trình trải nghiệm lái thử dành cho khách hàng quan tâm đến sedan, SUV và BMW Motorrad.\n\nĐội ngũ tư vấn sẽ hỗ trợ cấu hình xe, giải thích công nghệ nổi bật và ghi nhận nhu cầu báo giá sau sự kiện.",
                'published_at' => now()->subDays(5),
            ],
            [
                'title' => 'Chương trình tư vấn tài chính BMW',
                'category' => Article::CATEGORY_SALES_PROGRAMS,
                'excerpt' => 'Cập nhật chương trình tư vấn tài chính và đặt lịch gặp cố vấn bán hàng tại showroom BMW.',
                'body' => "Khách hàng đang tìm hiểu các lựa chọn sở hữu BMW có thể đăng ký lịch gặp cố vấn để nhận tư vấn tài chính phù hợp.\n\nChương trình tập trung vào minh bạch chi phí, cấu hình xe và kế hoạch bàn giao tại showroom.",
                'published_at' => now()->subDays(8),
            ],
        ];

        foreach ($articles as $article) {
            Article::updateOrCreate(
                ['slug' => Str::slug($article['title'])],
                [
                    'user_id' => $admin?->id,
                    'title' => $article['title'],
                    'category' => $article['category'],
                    'excerpt' => $article['excerpt'],
                    'body' => $article['body'],
                    'status' => Article::STATUS_PUBLISHED,
                    'published_at' => $article['published_at'],
                    'seo_title' => $article['title'],
                    'seo_description' => $article['excerpt'],
                ],
            );
        }
    }
}
