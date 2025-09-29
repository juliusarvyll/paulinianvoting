<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the new enum value 'department_course_level' to positions.level
        DB::statement("ALTER TABLE `positions` MODIFY `level` ENUM('university','department','course','year_level','department_course_level') NOT NULL");
    }

    public function down(): void
    {
        // Optional safety: convert any rows using the new value back to a supported one before reverting
        DB::statement("UPDATE `positions` SET `level` = 'department' WHERE `level` = 'department_course_level'");
        // Revert enum to original definition
        DB::statement("ALTER TABLE `positions` MODIFY `level` ENUM('university','department','course','year_level') NOT NULL");
    }
};
