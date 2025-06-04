<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultTags = [
            [
                'TagName' => 'post',
                'Description' => 'General posts and discussions'
            ],
            [
                'TagName' => 'offtopic',
                'Description' => 'Off-topic conversations and casual discussions'
            ],
            [
                'TagName' => 'announcement',
                'Description' => 'Important announcements and news'
            ],
            [
                'TagName' => 'help',
                'Description' => 'Help requests and support questions'
            ],
            [
                'TagName' => 'feedback',
                'Description' => 'User feedback and suggestions'
            ],
            [
                'TagName' => 'tutorial',
                'Description' => 'Educational content and how-to guides'
            ]
        ];

        foreach ($defaultTags as $tag) {
            Tag::firstOrCreate(
                ['TagName' => $tag['TagName']],
                $tag
            );
        }

        $this->command->info('Default tags created successfully!');
    }
}
