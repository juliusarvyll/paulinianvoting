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
        $votersTurnout = Voter::where('has_voted', true)->count();

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
        $votersTurnout = Voter::where('has_voted', true)->count();

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
        return Position::where('level', $level)
            ->with(['candidates' => function ($query) {
                $query->withCount('votes')
                    ->with('voter:id,first_name,last_name,middle_name');
            }])
            ->get();
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
        $votersTurnout = Voter::where('has_voted', true)->count();

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
        ]);
    }
}
