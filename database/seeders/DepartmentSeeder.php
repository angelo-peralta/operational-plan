<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Center for Planning, Research, Innovations, and New Technologies',
                'code' => 'CPRINT',
            ],
            [
                'name' => 'Information and Communications Technology',
                'code' => 'ICT',
            ],
            [
                'name' => 'Quality Assurance',
                'code' => 'QA',
            ],
            [
                'name' => 'Human Resources',
                'code' => 'HR',
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
            ],
            [
                'name' => 'Planning Office',
                'code' => 'PLAN',
            ],
        ];

        foreach ($departments as $department) {
            $alreadySeeded = Department::query()
                ->where('name', $department['name'])
                ->orWhere('code', $department['code'])
                ->exists();

            if ($alreadySeeded) {
                continue;
            }

            Department::query()->create([
                ...$department,
                'description' => null,
                'is_active' => true,
            ]);
        }
    }
}
