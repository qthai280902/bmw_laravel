<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1000, 9999),
            'category' => fake()->randomElement(Article::categoryValues()),
            'excerpt' => fake()->paragraph(),
            'body' => fake()->paragraphs(6, true),
            'cover_image' => null,
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subDays(fake()->numberBetween(1, 15)),
        ]);
    }
}
