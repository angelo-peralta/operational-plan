<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use Illuminate\Database\Seeder;

class OrganizationalUnitSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $root = OrganizationalUnit::query()->firstOrCreate(['code' => 'SPUP'], [
            'name' => 'St. Paul University Philippines', 'type' => 'university', 'sort_order' => 0,
        ]);
        $academic = OrganizationalUnit::query()->firstOrCreate(['code' => 'ACADEMIC'], [
            'name' => 'Academic Affairs', 'type' => 'vice_presidential_area', 'parent_id' => $root->id, 'sort_order' => 2,
        ]);
        $administrative = OrganizationalUnit::query()->firstOrCreate(['code' => 'ADMIN'], [
            'name' => 'Administrative and General Services', 'type' => 'vice_presidential_area', 'parent_id' => $root->id, 'sort_order' => 3,
        ]);

        $units = [
            ['PRESIDENT', 'Office of the President', 'executive_office', $root->id],
            ['CPRINT', 'Center for Planning, Research, Innovations, and New Technologies', 'center', $administrative->id],
            ['ICT', 'Information and Communications Technology', 'office', $administrative->id],
            ['FIN', 'Finance', 'vice_presidential_area', $root->id],
            ['QA', 'Quality Assurance', 'office', $administrative->id],
            ['HR', 'Human Resources', 'office', $administrative->id],
            ['GS', 'Graduate School', 'school', $academic->id],
            ['MED', 'School of Medicine', 'school', $academic->id],
            ['SASTE', 'School of Arts, Sciences and Teacher Education', 'school', $academic->id],
            ['SBAHM', 'School of Business, Accountancy and Hospitality Management', 'school', $academic->id],
            ['SITE', 'School of Information Technology and Engineering', 'school', $academic->id],
            ['SNAHS', 'School of Nursing and Allied Health Sciences', 'school', $academic->id],
            ['BEU', 'Basic Education Unit', 'unit', $academic->id],
        ];

        foreach ($units as $order => [$code, $name, $type, $parentId]) {
            OrganizationalUnit::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'type' => $type, 'parent_id' => $parentId,
                'acronym' => $code, 'sort_order' => $order + 3,
            ]);
        }
    }
}
