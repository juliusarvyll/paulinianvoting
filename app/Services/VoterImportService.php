<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Course;
use App\Models\Voter;
use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class VoterImportService
{
    protected $departments = [];
    protected $courses = [];

    public function import(string $jsonPath)
    {
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            throw new \Exception('Invalid JSON file');
        }

        DB::beginTransaction();
        try {
            $this->processDepartments($data['departments']);
            $this->processVoters($data['voters']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function processDepartments(array $departments)
    {
        foreach ($departments as $code => $info) {
            $department = Department::firstOrCreate(
                ['code' => $code],
                ['name' => $info['name']]
            );

            $this->departments[$code] = $department->id;

            foreach ($info['courses'] as $courseCode => $courseName) {
                $course = Course::firstOrCreate(
                    [
                        'code' => $courseCode,
                        'department_id' => $department->id
                    ],
                    ['name' => $courseName]
                );

                $this->courses[$courseCode] = $course->id;
            }
        }
    }

    protected function processVoters(array $voters)
    {
        foreach ($voters as $voterData) {
            // Create or update voter
            $voter = Voter::firstOrCreate(
                ['student_number' => $voterData['student_number']],
                [
                    'first_name' => $voterData['first_name'],
                    'last_name' => $voterData['last_name'],
                    'email' => $voterData['email'],
                    'department_id' => $this->departments[$voterData['department']],
                    'course_id' => $this->courses[$voterData['course']],
                    'year_level' => $voterData['year_level'],
                    'password' => Hash::make($voterData['student_number']), // Use student number as default password
                ]
            );

            // Process votes if they exist
            if (isset($voterData['votes'])) {
                $this->processVoterVotes($voter, $voterData['votes']);
            }
        }
    }

    protected function processVoterVotes(Voter $voter, array $votes)
    {
        foreach ($votes as $positionName => $candidateStudentNumber) {
            $candidate = Candidate::whereHas('voter', function ($query) use ($candidateStudentNumber) {
                $query->where('student_number', $candidateStudentNumber);
            })->first();

            if ($candidate) {
                Vote::firstOrCreate([
                    'voter_id' => $voter->id,
                    'candidate_id' => $candidate->id,
                    'position_id' => $candidate->position_id,
                ]);

                $voter->has_voted = true;
                $voter->save();
            }
        }
    }
}
