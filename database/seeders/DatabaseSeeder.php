<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\CourseSeeder;
use Database\Seeders\ShieldSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            ShieldSeeder::class, // Run ShieldSeeder first to set up roles and permissions
            DepartmentSeeder::class,
            CourseSeeder::class,
            // Add other seeders here
        ]);
    }
}
