<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        DB::table('departments')->insert([
            [
                'department_name' => 'SNAHS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'department_name' => 'SITE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'department_name' => 'SBAHM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'department_name' => 'SASTE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'department_name' => 'SOM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'department_name' => 'GRADUATE SCHOOL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'department_name' => 'ETEEAP',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
