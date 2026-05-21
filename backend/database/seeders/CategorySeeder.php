<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'general' => 'General',
            'news' => 'News',
            'sports' => 'Sports',
            'entertainment' => 'Entertainment',
            'music' => 'Music',
            'movies' => 'Movies',
            'documentary' => 'Documentary',
            'kids' => 'Kids',
            'education' => 'Education',
            'religious' => 'Religious',
            'business' => 'Business',
            'lifestyle' => 'Lifestyle',
            'culture' => 'Culture',
            'comedy' => 'Comedy',
            'drama' => 'Drama',
            'science' => 'Science',
            'travel' => 'Travel',
            'cooking' => 'Cooking',
            'animation' => 'Animation',
            'series' => 'Series',
        ];

        foreach ($categories as $slug => $name) {
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            );
        }
    }
}
