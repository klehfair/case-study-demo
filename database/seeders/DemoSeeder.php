<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sellers')->upsert(
            [
                ['id' => 1, 'name' => 'Sports Direct Demo', 'tone_preference' => 'energetic',    'product_category' => 'sports',      'created_at' => now(), 'updated_at' => now()],
                ['id' => 2, 'name' => 'TechZone Demo',       'tone_preference' => 'professional', 'product_category' => 'electronics', 'created_at' => now(), 'updated_at' => now()],
            ],
            ['id'],
            ['name', 'tone_preference', 'product_category', 'updated_at']
        );
    }
}
