<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Author' => User::factory(),
            'Topic' => fake()->sentence(4),
            'Content' => fake()->paragraphs(3, true),
            'Status' => 'published',
            'PostType' => 'main',
            'ParentPostID' => null,
            'LastEditedAt' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the post is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'Status' => 'draft',
        ]);
    }

    /**
     * Indicate that the post is a linked post.
     */
    public function linked(int $parentPostId): static
    {
        return $this->state(fn (array $attributes) => [
            'PostType' => 'linked',
            'ParentPostID' => $parentPostId,
        ]);
    }
}
