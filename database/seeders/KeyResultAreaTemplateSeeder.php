<?php

namespace Database\Seeders;

use App\Models\KeyResultAreaTemplate;
use Illuminate\Database\Seeder;

class KeyResultAreaTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KeyResultAreaTemplate::query()->firstOrCreate(
            ['code' => 'KRA 1'],
            [
                'name' => 'Quality Teaching, Learning, and Student Services',
                'description' => null,
                'sort_order' => 1,
                'is_active' => true,
            ],
        );
    }
}
