<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'ug', 'name' => 'Uganda', 'timezone' => 'Africa/Kampala'],
            ['code' => 'us', 'name' => 'United States', 'timezone' => 'America/New_York'],
            ['code' => 'gb', 'name' => 'United Kingdom', 'timezone' => 'Europe/London'],
            ['code' => 'ca', 'name' => 'Canada', 'timezone' => 'America/Toronto'],
            ['code' => 'de', 'name' => 'Germany', 'timezone' => 'Europe/Berlin'],
            ['code' => 'fr', 'name' => 'France', 'timezone' => 'Europe/Paris'],
            ['code' => 'in', 'name' => 'India', 'timezone' => 'Asia/Kolkata'],
            ['code' => 'ng', 'name' => 'Nigeria', 'timezone' => 'Africa/Lagos'],
            ['code' => 'ke', 'name' => 'Kenya', 'timezone' => 'Africa/Nairobi'],
            ['code' => 'za', 'name' => 'South Africa', 'timezone' => 'Africa/Johannesburg'],
            ['code' => 'tz', 'name' => 'Tanzania', 'timezone' => 'Africa/Dar_es_Salaam'],
            ['code' => 'rw', 'name' => 'Rwanda', 'timezone' => 'Africa/Kigali'],
            ['code' => 'br', 'name' => 'Brazil', 'timezone' => 'America/Sao_Paulo'],
            ['code' => 'it', 'name' => 'Italy', 'timezone' => 'Europe/Rome'],
            ['code' => 'es', 'name' => 'Spain', 'timezone' => 'Europe/Madrid'],
            ['code' => 'jp', 'name' => 'Japan', 'timezone' => 'Asia/Tokyo'],
            ['code' => 'cn', 'name' => 'China', 'timezone' => 'Asia/Shanghai'],
            ['code' => 'au', 'name' => 'Australia', 'timezone' => 'Australia/Sydney'],
            ['code' => 'mx', 'name' => 'Mexico', 'timezone' => 'America/Mexico_City'],
            ['code' => 'ar', 'name' => 'Argentina', 'timezone' => 'America/Argentina/Buenos_Aires'],
            ['code' => 'ec', 'name' => 'Ecuador', 'timezone' => 'America/Guayaquil'],
            ['code' => 'co', 'name' => 'Colombia', 'timezone' => 'America/Bogota'],
            ['code' => 'pe', 'name' => 'Peru', 'timezone' => 'America/Lima'],
            ['code' => 'cl', 'name' => 'Chile', 'timezone' => 'America/Santiago'],
            ['code' => 'pt', 'name' => 'Portugal', 'timezone' => 'Europe/Lisbon'],
            ['code' => 'nl', 'name' => 'Netherlands', 'timezone' => 'Europe/Amsterdam'],
            ['code' => 'se', 'name' => 'Sweden', 'timezone' => 'Europe/Stockholm'],
            ['code' => 'no', 'name' => 'Norway', 'timezone' => 'Europe/Oslo'],
            ['code' => 'pl', 'name' => 'Poland', 'timezone' => 'Europe/Warsaw'],
            ['code' => 'tr', 'name' => 'Turkey', 'timezone' => 'Europe/Istanbul'],
            ['code' => 'ru', 'name' => 'Russia', 'timezone' => 'Europe/Moscow'],
            ['code' => 'kr', 'name' => 'South Korea', 'timezone' => 'Asia/Seoul'],
            ['code' => 'ae', 'name' => 'United Arab Emirates', 'timezone' => 'Asia/Dubai'],
            ['code' => 'sa', 'name' => 'Saudi Arabia', 'timezone' => 'Asia/Riyadh'],
            ['code' => 'eg', 'name' => 'Egypt', 'timezone' => 'Africa/Cairo'],
            ['code' => 'ma', 'name' => 'Morocco', 'timezone' => 'Africa/Casablanca'],
            ['code' => 'gh', 'name' => 'Ghana', 'timezone' => 'Africa/Accra'],
            ['code' => 'et', 'name' => 'Ethiopia', 'timezone' => 'Africa/Addis_Ababa'],
            ['code' => 'dz', 'name' => 'Algeria', 'timezone' => 'Africa/Algiers'],
            ['code' => 'ao', 'name' => 'Angola', 'timezone' => 'Africa/Luanda'],
        ];

        foreach ($countries as $data) {
            Country::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'flag_url' => null,
                    'timezone' => $data['timezone'],
                    'is_active' => true,
                ]
            );
        }
    }

}
