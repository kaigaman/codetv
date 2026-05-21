<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'eng', 'name' => 'English'],
            ['code' => 'lug', 'name' => 'Luganda'],
            ['code' => 'swa', 'name' => 'Swahili'],
            ['code' => 'fra', 'name' => 'French'],
            ['code' => 'spa', 'name' => 'Spanish'],
            ['code' => 'por', 'name' => 'Portuguese'],
            ['code' => 'deu', 'name' => 'German'],
            ['code' => 'ita', 'name' => 'Italian'],
            ['code' => 'rus', 'name' => 'Russian'],
            ['code' => 'ara', 'name' => 'Arabic'],
            ['code' => 'hin', 'name' => 'Hindi'],
            ['code' => 'chi', 'name' => 'Chinese'],
            ['code' => 'jpn', 'name' => 'Japanese'],
            ['code' => 'kor', 'name' => 'Korean'],
            ['code' => 'tur', 'name' => 'Turkish'],
            ['code' => 'nld', 'name' => 'Dutch'],
            ['code' => 'swe', 'name' => 'Swedish'],
            ['code' => 'nor', 'name' => 'Norwegian'],
            ['code' => 'pol', 'name' => 'Polish'],
            ['code' => 'ben', 'name' => 'Bengali'],
            ['code' => 'tam', 'name' => 'Tamil'],
            ['code' => 'tel', 'name' => 'Telugu'],
            ['code' => 'mar', 'name' => 'Marathi'],
            ['code' => 'urd', 'name' => 'Urdu'],
            ['code' => 'hau', 'name' => 'Hausa'],
            ['code' => 'ibo', 'name' => 'Igbo'],
            ['code' => 'yor', 'name' => 'Yoruba'],
            ['code' => 'amh', 'name' => 'Amharic'],
            ['code' => 'som', 'name' => 'Somali'],
            ['code' => 'lin', 'name' => 'Lingala'],
            ['code' => 'nya', 'name' => 'Chichewa'],
            ['code' => 'run', 'name' => 'Kirundi'],
            ['code' => 'kin', 'name' => 'Kinyarwanda'],
            ['code' => 'ssw', 'name' => 'Swati'],
            ['code' => 'tsn', 'name' => 'Tswana'],
            ['code' => 'sot', 'name' => 'Sotho'],
            ['code' => 'zul', 'name' => 'Zulu'],
            ['code' => 'xho', 'name' => 'Xhosa'],
            ['code' => 'afr', 'name' => 'Afrikaans'],
        ];

        foreach ($languages as $lang) {
            Language::firstOrCreate(
                ['code' => $lang['code']],
                ['name' => $lang['name']]
            );
        }
    }
}
