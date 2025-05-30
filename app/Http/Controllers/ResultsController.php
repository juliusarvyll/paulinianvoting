<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Position;
use App\Models\Voter;
use App\Models\Vote;
use App\Models\Candidate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ResultsController extends Controller
{
    /**
     * Display the results page.
     */
    public function index()
    {
        // Get active election
        $election = Election::where('is_active', true)->first();

        if (!$election) {
            return redirect()->route('welcome')
                ->with('error', 'No active election at the moment.');
        }

        // Get positions and candidates with vote counts
        $universityPositions = $this->getPositionsWithCandidates('university');
        $departmentPositions = $this->getPositionsWithCandidates('department');
        $coursePositions = $this->getPositionsWithCandidates('course');
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level');

        // Get voter statistics
        $totalVoters = Voter::count();
        $votersTurnout = \App\Models\Vote::distinct('voter_id')->count('voter_id');
        $departmentVoterCounts = Voter::select('department_id', DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->pluck('count', 'department_id');

        return Inertia::render('Results/Index', [
            'election' => $election,
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
            ],
            'initialTotalVoters' => $totalVoters,
            'initialVotersTurnout' => $votersTurnout,
            'departmentVoterCounts' => $departmentVoterCounts,
        ]);
    }

    /**
     * Provide real-time results data for AJAX requests.
     */
    public function data()
    {
        // Get positions and candidates with vote counts
        $universityPositions = $this->getPositionsWithCandidates('university');
        $departmentPositions = $this->getPositionsWithCandidates('department');
        $coursePositions = $this->getPositionsWithCandidates('course');
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level');

        // Get voter statistics
        $totalVoters = Voter::count();
        $votersTurnout = \App\Models\Vote::distinct('voter_id')->count('voter_id');

        return response()->json([
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
            ],
            'totalVoters' => $totalVoters,
            'votersTurnout' => $votersTurnout,
        ]);
    }

    /**
     * Get positions with candidates and vote counts for a specific level.
     */
    private function getPositionsWithCandidates($level)
    {
        $positions = Position::where('level', $level)
            ->with([
                'candidates' => function ($query) {
                    $query->withCount('votes')
                        ->with([
                            'voter:id,first_name,last_name,middle_name,course_id',
                            'department',
                            'voter.course.department',
                        ]);
                }
            ])
            ->get();

        // For university-level positions, attach department vote counts
        if ($level === 'university') {
            // First, get all departments to ensure we include departments with zero votes
            $allDepartments = DB::table('departments')->get();

            $positions->transform(function ($position) use ($allDepartments) {
                $position->candidates->transform(function ($candidate) use ($allDepartments) {
                    // Get votes grouped by department for this candidate
                    $departmentVotes = Vote::where('votes.candidate_id', $candidate->id)
                        ->rightJoin('voters', 'votes.voter_id', '=', 'voters.id')
                        ->rightJoin('courses', 'voters.course_id', '=', 'courses.id')
                        ->rightJoin('departments', 'courses.department_id', '=', 'departments.id')
                        ->select(
                            'departments.id as department_id',
                            'departments.department_name',
                            DB::raw('COUNT(votes.id) as votes')
                        )
                        ->groupBy('departments.id', 'departments.department_name')
                        ->get()
                        ->keyBy('department_id');

                    // Get total voters per department
                    $departmentTotals = Voter::join('courses', 'voters.course_id', '=', 'courses.id')
                        ->join('departments', 'courses.department_id', '=', 'departments.id')
                        ->select('departments.id', DB::raw('count(*) as total_voters'))
                        ->groupBy('departments.id')
                        ->pluck('total_voters', 'id');

                    // Format the department votes, ensuring all departments are included
                    $formattedDepartmentVotes = [];
                    foreach ($allDepartments as $dept) {
                        $deptVote = $departmentVotes->get($dept->id);
                        $formattedDepartmentVotes[$dept->id] = [
                            'votes' => $deptVote ? $deptVote->votes : 0,
                            'totalVoters' => $departmentTotals[$dept->id] ?? 0,
                            'departmentName' => $dept->department_name
                        ];
                    }

                    $candidate->department_votes = $formattedDepartmentVotes;
                    return $candidate;
                });
                return $position;
            });
        }

        // For department-level positions, attach department directly to each candidate
        if ($level === 'department') {
            $positions->transform(function ($position) {
                $position->candidates->transform(function ($candidate) {
                    // Always provide department directly, fallback to voter's course department if needed
                    $department = $candidate->department ?: ($candidate->voter->course->department ?? null);
                    $candidate->department = $department;
                    return $candidate;
                });
                return $position;
            });
        }

        return $positions;
    }

    /**
     * Display the public results page.
     */
    public function public()
    {
        // Get active election
        $election = Election::where('is_active', true)->first();

        if (!$election) {
            return redirect()->route('welcome')
                ->with('error', 'No active election at the moment.');
        }

        // Get positions and candidates with vote counts
        $universityPositions = $this->getPositionsWithCandidates('university');
        $departmentPositions = $this->getPositionsWithCandidates('department');
        $coursePositions = $this->getPositionsWithCandidates('course');
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level');

        // Get voter statistics
        $totalVoters = Voter::count();
        $votersTurnout = \App\Models\Vote::distinct('voter_id')->count('voter_id');
        $departmentVoterCounts = Voter::select('department_id', DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->pluck('count', 'department_id');

        return Inertia::render('Results/Public', [
            'election' => $election,
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
            ],
            'initialTotalVoters' => $totalVoters,
            'initialVotersTurnout' => $votersTurnout,
            'departmentVoterCounts' => $departmentVoterCounts,
        ]);
    }
}
