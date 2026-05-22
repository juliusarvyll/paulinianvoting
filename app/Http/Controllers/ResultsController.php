<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Position;
use App\Models\Voter;
use App\Models\Vote;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ResultsController extends Controller
{
    /**
     * Get the distinct number of voters who cast at least one vote in an election.
     */
    private function getElectionTurnoutCount(int $electionId): int
    {
        return Vote::where('election_id', $electionId)
            ->distinct()
            ->count('voter_id');
    }

    /**
     * Get per-department count of voters who cast at least one vote in an election.
     */
    private function getDepartmentTurnoutCounts(int $electionId): array
    {
        return Vote::where('votes.election_id', $electionId)
            ->join('voters', 'votes.voter_id', '=', 'voters.id')
            ->join('courses', 'voters.course_id', '=', 'courses.id')
            ->select('courses.department_id', DB::raw('count(distinct votes.voter_id) as count'))
            ->groupBy('courses.department_id')
            ->pluck('count', 'courses.department_id')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /**
     * Get total registered voters grouped by department.
     */
    private function getDepartmentVoterCounts()
    {
        return Voter::join('courses', 'voters.course_id', '=', 'courses.id')
            ->select('courses.department_id', DB::raw('count(*) as count'))
            ->groupBy('courses.department_id')
            ->pluck('count', 'courses.department_id');
    }

    /**
     * Get total registered voters grouped by department and year level.
     */
    private function getDepartmentYearLevelVoterCounts(): array
    {
        $counts = Voter::join('courses', 'voters.course_id', '=', 'courses.id')
            ->select('courses.department_id as department_id', 'voters.year_level', DB::raw('count(*) as count'))
            ->groupBy('courses.department_id', 'voters.year_level')
            ->get();

        $formattedCounts = [];

        foreach ($counts as $row) {
            $deptId = (string) $row->department_id;
            $year = (string) $row->year_level;

            if (!isset($formattedCounts[$deptId])) {
                $formattedCounts[$deptId] = [];
            }

            $formattedCounts[$deptId][$year] = (int) $row->count;
        }

        return $formattedCounts;
    }

    /**
     * Build the shared results payload for both initial render and refreshes.
     */
    private function getResultsPayload(Election $election): array
    {
        $electionId = $election->id;

        $universityPositions = $this->getPositionsWithCandidates('university', $electionId);
        $departmentPositions = $this->getPositionsWithCandidates('department', $electionId);
        $coursePositions = $this->getPositionsWithCandidates('course', $electionId);
        $yearLevelPositions = $this->getPositionsWithCandidates('year_level', $electionId);
        $departmentYearLevelPositions = $this->getPositionsWithCandidates('department_year_level', $electionId);

        $totalVoters = Voter::count();
        $votersTurnout = $this->getElectionTurnoutCount($electionId);
        $departmentVoterCounts = $this->getDepartmentVoterCounts();
        $departmentTurnoutCounts = $this->getDepartmentTurnoutCounts($electionId);
        $departmentYearLevelVoterCounts = $this->getDepartmentYearLevelVoterCounts();
        $departments = DB::table('departments')->select('id', 'department_name')->get();

        return [
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
                'department_year_level' => $departmentYearLevelPositions,
            ],
            'totalVoters' => $totalVoters,
            'votersTurnout' => $votersTurnout,
            'departmentVoterCounts' => $departmentVoterCounts,
            'departmentTurnoutCounts' => $departmentTurnoutCounts,
            'departmentYearLevelVoterCounts' => $departmentYearLevelVoterCounts,
            'departments' => $departments,
        ];
    }

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

        $resultsPayload = $this->getResultsPayload($election);

        return Inertia::render('Results/Index', [
            'election' => $election,
            'positions' => $resultsPayload['positions'],
            'initialTotalVoters' => $resultsPayload['totalVoters'],
            'initialVotersTurnout' => $resultsPayload['votersTurnout'],
            'departmentVoterCounts' => $resultsPayload['departmentVoterCounts'],
            'departmentTurnoutCounts' => $resultsPayload['departmentTurnoutCounts'],
            'departmentYearLevelVoterCounts' => $resultsPayload['departmentYearLevelVoterCounts'],
            'departments' => $resultsPayload['departments'],
        ]);
    }

    /**
     * Provide real-time results data for AJAX requests.
     */
    public function data()
    {
        // Get active election
        $election = Election::active()->first();

        if (!$election) {
            return response()->json(['message' => 'No active election at the moment.'], 404);
        }

        return response()->json($this->getResultsPayload($election));
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

        $resultsPayload = $this->getResultsPayload($election);

        return Inertia::render('Results/Public', [
            'election' => $election,
            'positions' => $resultsPayload['positions'],
            'initialTotalVoters' => $resultsPayload['totalVoters'],
            'initialVotersTurnout' => $resultsPayload['votersTurnout'],
            'departmentVoterCounts' => $resultsPayload['departmentVoterCounts'],
            'departmentTurnoutCounts' => $resultsPayload['departmentTurnoutCounts'],
            'departmentYearLevelVoterCounts' => $resultsPayload['departmentYearLevelVoterCounts'],
            'departments' => $resultsPayload['departments'],
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
