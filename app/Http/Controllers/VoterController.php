<?php

namespace App\Http\Controllers;

use App\Models\Voter;
use App\Models\Election;
use App\Models\Position;
use App\Models\Candidate;
use App\Models\Vote;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class VoterController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'code' => 'required|exists:voters,code'
        ]);

        $voter = Voter::where('code', $request->code)->first();

        if ($voter->has_voted) {
            return back()->withErrors([
                'code' => 'You have already voted.'
            ]);
        }

        // Store voter in session
        session(['voter_id' => $voter->id]);

        // Redirect to voting page
        return redirect()->route('voting');
    }

    public function voting()
    {
        if (!session('voter_id')) {
            return redirect()->route('welcome');
        }

        $voter = Voter::with(['course.department'])->findOrFail(session('voter_id'));

        // Get active election
        $election = Election::where('is_active', true)->first();

        if (!$election) {
            return back()->withErrors([
                'election' => 'No active election at the moment.'
            ]);
        }

        // Get positions and candidates based on levels
        $universityPositions = Position::where('level', 'university')
            ->with(['candidates' => function ($query) {
                $query->with('voter:id,first_name,last_name,middle_name');
            }])
            ->get();

        $departmentPositions = Position::where('level', 'department')
            ->with(['candidates' => function ($query) use ($voter) {
                $query->where('department_id', $voter->course->department_id)
                    ->with('voter:id,first_name,last_name,middle_name');
            }])
            ->get();

        $coursePositions = Position::where('level', 'course')
            ->with(['candidates' => function ($query) use ($voter) {
                $query->where('course_id', $voter->course_id)
                    ->with('voter:id,first_name,last_name,middle_name');
            }])
            ->get();

        $yearLevelPositions = Position::where('level', 'year_level')
            ->with(['candidates' => function ($query) use ($voter) {
                $query->whereHas('voter', function ($q) use ($voter) {
                    $q->where('year_level', $voter->year_level);
                })->with('voter:id,first_name,last_name,middle_name');
            }])
            ->get();

        return Inertia::render('Voting/Index', [
            'voter' => $voter,
            'election' => $election,
            'positions' => [
                'university' => $universityPositions,
                'department' => $departmentPositions,
                'course' => $coursePositions,
                'year_level' => $yearLevelPositions,
            ],
        ]);
    }

    public function castVote(Request $request)
    {
        // Validate inputs
        $request->validate([
            'votes' => ['nullable', 'array'],
            'votes.*.candidate_id' => ['required_with:votes', 'exists:candidates,id'],
            'votes.*.position_id' => ['required_with:votes', 'exists:positions,id'],
            'voter_id' => ['sometimes', 'exists:voters,id'],
        ]);

        try {
            DB::beginTransaction();

            // Get voter from session
            $voterId = session('voter_id');

            if (!$voterId) {
                return redirect()->route('welcome')
                    ->with('error', 'Your session has expired. Please login again.');
            }

            $voter = Voter::findOrFail($voterId);
            $election = Election::where('is_active', true)->first();

            if (!$election) {
                return redirect()->back()
                    ->with('error', 'No active election found.');
            }

            // Check if voter has already voted
            if ($voter->has_voted) {
                return redirect()->back()
                    ->with('error', 'You have already cast your vote.');
            }

            // Log received data for debugging
            \Log::info('Received vote data:', [
                'votes' => $request->votes,
                'voter_id' => $request->voter_id,
                'session_voter_id' => $voterId
            ]);

            // Record votes if any were submitted
            if ($request->has('votes') && !empty($request->votes)) {
                foreach ($request->votes as $vote) {
                    // Verify that the candidate belongs to the position
                    $candidate = Candidate::where('id', $vote['candidate_id'])
                        ->where('position_id', $vote['position_id'])
                        ->firstOrFail();

                    // Create the vote record using server-validated IDs
                    Vote::create([
                        'voter_id' => $voter->id, // Always use the authenticated voter from session
                        'candidate_id' => $vote['candidate_id'],
                        'position_id' => $vote['position_id'],
                    ]);
                }
            }

            // Mark voter as voted
            $voter->update(['has_voted' => true]);

            DB::commit();

            // Prepare the response first before clearing session
            $response = redirect()->route('welcome')
                ->with('success', 'Your vote has been recorded successfully.');

            // Clear voter session after preparing the response
            session()->forget('voter_id');

            // Return the already prepared response
            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            // Log the error
            \Log::error('Vote casting error: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'An error occurred while recording your vote: ' . $e->getMessage());
        }
    }

    public function logout()
    {
        session()->forget('voter_id');
        return redirect()->route('welcome');
    }

    public function showRegistrationForm()
    {
        $courses = Course::select('id', 'course_name')->get();
        return Inertia::render('voter/register', [
            'courses' => $courses
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:voters,code',
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'sex' => 'required|in:M,F',
            'course_id' => 'required|exists:courses,id',
            'year_level' => 'required|integer|between:1,4',
        ]);

        try {
            $voter = Voter::create([
                'code' => $request->code,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'sex' => $request->sex,
                'course_id' => $request->course_id,
                'year_level' => $request->year_level,
                'has_voted' => false,
            ]);

            return redirect()->route('welcome')
                ->with('success', 'Registration successful! You can now login with your student ID.');

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => 'An error occurred during registration. Please try again.'
            ]);
        }
    }
}
