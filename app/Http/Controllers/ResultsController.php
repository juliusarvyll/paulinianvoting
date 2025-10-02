<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Position;
use App\Models\Voter;
use App\Models\Vote;
use App\Models\Candidate;
use App\Models\VoterElectionParticipation;
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
        $election = Election::active()->first();

        if (!$election) {
            return redirect()->route('welcome')
                ->with('error', 'No active election at the moment.');
        }

        // Get positions and candidates with vote counts (scoped to active election)
        $universityPositions = $this->getPositionsWithCandidates('university', $election->id);
        $departmentPositions = $this->getPositionsWithCandidates('department', $election->id);
        $coursePositions = $this->getPositionsWithCandidates('course', $election->id);
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level', $election->id);
        $departmentYearLevelPositions = $this->getPositionsWithCandidates('department_year_level', $election->id);

        // Get voter statistics (turnout by participation for active election)
        $totalVoters = Voter::count();
        $votersTurnout = VoterElectionParticipation::where('election_id', $election->id)
            ->distinct('voter_id')
            ->count('voter_id');
        $departmentVoterCounts = Voter::select('department_id', DB::raw('count(*) as count'))
            ->groupBy('department_id')
            ->pluck('count', 'department_id');
        // Voter counts per Department x Year Level (for department_year_level percentages)
        $deptYearCountsRawPublic = Voter::join('courses', 'voters.course_id', '=', 'courses.id')
            ->select('courses.department_id as department_id', 'voters.year_level', DB::raw('count(*) as count'))
            ->groupBy('courses.department_id', 'voters.year_level')
            ->get();
        $departmentYearLevelVoterCountsPublic = [];
        foreach ($deptYearCountsRawPublic as $row) {
            $deptId = (string) $row->department_id;
            $year = (string) $row->year_level;
            if (!isset($departmentYearLevelVoterCountsPublic[$deptId])) {
                $departmentYearLevelVoterCountsPublic[$deptId] = [];
            }
            $departmentYearLevelVoterCountsPublic[$deptId][$year] = (int) $row->count;
        }
        // Voter counts per Department x Year Level
        $deptYearCountsRaw = Voter::join('courses', 'voters.course_id', '=', 'courses.id')
            ->select('courses.department_id as department_id', 'voters.year_level', DB::raw('count(*) as count'))
            ->groupBy('courses.department_id', 'voters.year_level')
            ->get();
        $departmentYearLevelVoterCounts = [];
        foreach ($deptYearCountsRaw as $row) {
            $deptId = (string) $row->department_id;
            $year = (string) $row->year_level;
            if (!isset($departmentYearLevelVoterCounts[$deptId])) {
                $departmentYearLevelVoterCounts[$deptId] = [];
            }
            $departmentYearLevelVoterCounts[$deptId][$year] = (int) $row->count;
        }
        $departments = DB::table('departments')->select('id', 'department_name')->get();

        return Inertia::render('Results/Index', [
            'election' => $election,
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
                'department_year_level' => $departmentYearLevelPositions,
            ],
            'initialTotalVoters' => $totalVoters,
            'initialVotersTurnout' => $votersTurnout,
            'departmentVoterCounts' => $departmentVoterCounts,
            'departmentYearLevelVoterCounts' => $departmentYearLevelVoterCounts,
            'departments' => $departments,
        ]);
    }

    /**
     * Provide real-time results data for AJAX requests.
     */
    public function data()
    {
        // Get active election
        $election = Election::active()->first();

        // Get positions and candidates with vote counts (scoped to active election)
        $universityPositions = $this->getPositionsWithCandidates('university', $election->id);
        $departmentPositions = $this->getPositionsWithCandidates('department', $election->id);
        $coursePositions = $this->getPositionsWithCandidates('course', $election->id);
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level', $election->id);
        $departmentYearLevelPositions = $this->getPositionsWithCandidates('department_year_level', $election->id);

        // Get voter statistics (turnout by participation for active election)
        $totalVoters = Voter::count();
        $votersTurnout = VoterElectionParticipation::where('election_id', $election->id)
            ->distinct('voter_id')
            ->count('voter_id');

        return response()->json([
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
                'department_year_level' => $departmentYearLevelPositions,
            ],
            'totalVoters' => $totalVoters,
            'votersTurnout' => $votersTurnout,
        ]);
    }

    /**
     * Get positions with candidates and vote counts for a specific level.
     */
    private function getPositionsWithCandidates($level, $electionId)
    {
        $positions = Position::where('level', $level)
            ->where('election_id', $electionId)
            ->with([
                'candidates' => function ($query) use ($electionId) {
                    $query->withCount([
                        'votes as votes_count' => function ($q) use ($electionId) {
                            $q->where('election_id', $electionId);
                        },
                    ])
                        ->with([
                            'voter:id,first_name,last_name,middle_name,course_id,year_level',
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

            $positions->transform(function ($position) use ($allDepartments, $electionId) {
                $position->candidates->transform(function ($candidate) use ($allDepartments, $electionId) {
                    // Get votes grouped by department for this candidate
                    $departmentVotes = Vote::where('votes.candidate_id', $candidate->id)
                        ->where('votes.election_id', $electionId)
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
        if ($level === 'department' || $level === 'department_year_level') {
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

        // For department + year level positions, compute winners per Department x Year Level, respecting max_winners
        if ($level === 'department_year_level') {
            $positions->transform(function ($position) {
                $groups = [];
                foreach ($position->candidates as $candidate) {
                    $deptId = optional($candidate->department ?: optional($candidate->voter->course)->department)->id;
                    $deptName = optional($candidate->department ?: optional($candidate->voter->course)->department)->department_name;
                    $yearLevel = $candidate->voter->year_level ?? null;

                    if ($deptId === null || $yearLevel === null) {
                        // Skip candidates we cannot attribute to a department/year
                        continue;
                    }

                    if (!isset($groups[$deptId])) {
                        $groups[$deptId] = ['departmentName' => $deptName, 'years' => []];
                    }
                    if (!isset($groups[$deptId]['years'][$yearLevel])) {
                        $groups[$deptId]['years'][$yearLevel] = [];
                    }
                    $groups[$deptId]['years'][$yearLevel][] = $candidate;
                }

                // Sort each group by votes_count desc and slice by max_winners
                $max = (int) ($position->max_winners ?? 1);
                foreach ($groups as $deptId => $dept) {
                    foreach ($dept['years'] as $yr => $cands) {
                        usort($cands, function ($a, $b) {
                            return ($b->votes_count <=> $a->votes_count);
                        });
                        $groups[$deptId]['years'][$yr] = array_slice($cands, 0, max(1, $max));
                    }
                }

                // Attach computed winners
                $position->winners_by_department_year = $groups;
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
        $election = Election::active()->first();

        if (!$election) {
            return redirect()->route('welcome')
                ->with('error', 'No active election at the moment.');
        }

        // Get positions and candidates with vote counts (scoped to active election)
        $universityPositions = $this->getPositionsWithCandidates('university', $election->id);
        $departmentPositions = $this->getPositionsWithCandidates('department', $election->id);
        $coursePositions = $this->getPositionsWithCandidates('course', $election->id);
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level', $election->id);
        $departmentYearLevelPositions = $this->getPositionsWithCandidates('department_year_level', $election->id);

        // Get voter statistics (turnout by participation for active election)
        $totalVoters = Voter::count();
        $votersTurnout = VoterElectionParticipation::where('election_id', $election->id)
            ->distinct('voter_id')
            ->count('voter_id');
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
                'department_year_level' => $departmentYearLevelPositions,
            ],
            'initialTotalVoters' => $totalVoters,
            'initialVotersTurnout' => $votersTurnout,
            'departmentVoterCounts' => $departmentVoterCounts,
            'departmentYearLevelVoterCounts' => $departmentYearLevelVoterCountsPublic,
            'departments' => $departments,
        ]);
    }

    /**
     * Return voters for a given department (paginated) for on-demand display in results UI.
     */
    public function votersByDepartment(Request $request)
    {
        $departmentId = $request->query('department_id');
        if (!$departmentId) {
            return response()->json(['error' => 'department_id is required'], 422);
        }

        $perPage = (int) $request->query('per_page', 50);
        $search = trim((string) $request->query('q', ''));

        $query = Voter::query()
            ->select('voters.id', 'voters.first_name', 'voters.last_name', 'voters.middle_name', 'voters.year_level', 'voters.course_id')
            ->join('courses', 'voters.course_id', '=', 'courses.id')
            ->where('courses.department_id', $departmentId)
            ->with(['course:id,name']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('voters.first_name', 'like', "%$search%")
                  ->orWhere('voters.last_name', 'like', "%$search%")
                  ->orWhere('voters.middle_name', 'like', "%$search%");
            });
        }

        $voters = $query->orderBy('voters.last_name')->orderBy('voters.first_name')->paginate($perPage);

        return response()->json($voters);
    }
}
