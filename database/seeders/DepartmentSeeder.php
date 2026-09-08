<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/** Compatibility entry point for the former flat development directory. */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OrganizationalUnitSeeder::class);
    }
}
