<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;

class CourseSeeder extends Seeder
{
    public function run()
    {
        // Get department IDs
        $snahs = Department::where('department_name', 'SNAHS')->first()->id;
        $site = Department::where('department_name', 'SITE')->first()->id;
        $sbahm = Department::where('department_name', 'SBAHM')->first()->id;
        $saste = Department::where('department_name', 'SASTE')->first()->id;
        $eteeap = Department::where('department_name', 'ETEEAP')->first()->id;

        // Insert courses for each department
        DB::table('courses')->insert([
            // SNAHS Courses
            [
                'course_name' => 'Bachelor of Science in Nursing',
                'course_abbreviation' => 'BSN',
                'department_id' => $snahs,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Pharmacy',
                'course_abbreviation' => 'BSPHAR',
                'department_id' => $snahs,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Medical Technology',
                'course_abbreviation' => 'BSMT',
                'department_id' => $snahs,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Physical Therapy',
                'course_abbreviation' => 'BSPT',
                'department_id' => $snahs,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Radiologic Technology',
                'course_abbreviation' => 'BSRT',
                'department_id' => $snahs,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // SITE Courses
            [
                'course_name' => 'Bachelor of Science in Information Technology',
                'course_abbreviation' => 'BSIT',
                'department_id' => $site,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Library and Information Science',
                'course_abbreviation' => 'BLIS',
                'department_id' => $site,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Civil Engineering',
                'course_abbreviation' => 'BSCE',
                'department_id' => $site,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Computer Engineering',
                'course_abbreviation' => 'BSCOE',
                'department_id' => $site,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // SBAHM Courses
            [
                'course_name' => 'Bachelor of Science in Accountancy',
                'course_abbreviation' => 'BSA',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Entrepreneurship',
                'course_abbreviation' => 'BS ENTREP',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Business Administration major in Marketing Management',
                'course_abbreviation' => 'BSBA-MM',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Business Administration major in Financial Management',
                'course_abbreviation' => 'BSBA-FM',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Business Administration major in Operations Management',
                'course_abbreviation' => 'BSBA-OM',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Management Accounting',
                'course_abbreviation' => 'BSMA',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Hospitality Management',
                'course_abbreviation' => 'BSHM',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Tourism Management',
                'course_abbreviation' => 'BSTM',
                'department_id' => $sbahm,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // SASTE Courses
            [
                'course_name' => 'Bachelor of Arts in English Language Studies',
                'course_abbreviation' => 'BAELS',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Psychology',
                'course_abbreviation' => 'BSPSY',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Biology',
                'course_abbreviation' => 'BSBIO',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Social Work',
                'course_abbreviation' => 'BSSW',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Public Administration',
                'course_abbreviation' => 'BSPA',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Biology Major in MicroBiology',
                'course_abbreviation' => 'BSBIOLOGY',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Secondary Education',
                'course_abbreviation' => 'BSED',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Secondary Education major in Science',
                'course_abbreviation' => 'BSEDSCI',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Secondary Education major in Mathematics',
                'course_abbreviation' => 'BSEDMATH',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Secondary Education major in Social Studies',
                'course_abbreviation' => 'BSED SOCSCI',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Elementary Education',
                'course_abbreviation' => 'BEED',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Physical Education',
                'course_abbreviation' => 'BPED',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Environmental Science',
                'course_abbreviation' => 'BSENSE',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Environmental Science Professional',
                'course_abbreviation' => 'BSENSE PROF',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Secondary Education',
                'course_abbreviation' => 'BSEDE',
                'department_id' => $saste,
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // ETEEAP Courses
            [
                'course_name' => 'Bachelor of Science in Business Administration major in Operations Management',
                'course_abbreviation' => 'BSBA-OM (ETEEAP)',
                'department_id' => $eteeap,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Information Technology',
                'course_abbreviation' => 'BSIT (ETEEAP)',
                'department_id' => $eteeap,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'course_name' => 'Bachelor of Science in Hotel and Restaurant Management',
                'course_abbreviation' => 'BSHRM (ETEEAP)',
                'department_id' => $eteeap,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
