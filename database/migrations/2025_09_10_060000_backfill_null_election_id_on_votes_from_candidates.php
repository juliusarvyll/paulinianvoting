<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $processedKeys = [];

        DB::table('votes')
            ->join('candidates', 'votes.candidate_id', '=', 'candidates.id')
            ->whereNull('votes.election_id')
            ->whereNotNull('candidates.election_id')
            ->select(
                'votes.id',
                'votes.voter_id',
                'votes.position_id',
                'candidates.election_id as target_election_id'
            )
            ->orderBy('votes.id')
            ->lazy()
            ->each(function ($vote) use (&$processedKeys) {
                $key = implode(':', [
                    $vote->voter_id,
                    $vote->target_election_id,
                    $vote->position_id,
                ]);

                $alreadyExists = isset($processedKeys[$key]) || DB::table('votes')
                    ->where('voter_id', $vote->voter_id)
                    ->where('election_id', $vote->target_election_id)
                    ->where('position_id', $vote->position_id)
                    ->exists();

                if ($alreadyExists) {
                    DB::table('votes')
                        ->where('id', $vote->id)
                        ->delete();

                    return;
                }

                DB::table('votes')
                    ->where('id', $vote->id)
                    ->update([
                        'election_id' => $vote->target_election_id,
                    ]);

                $processedKeys[$key] = true;
            });

        DB::table('votes')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('voters')
                    ->whereColumn('voters.id', 'votes.voter_id');
            })
            ->delete();

        DB::table('votes')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('candidates')
                    ->whereColumn('candidates.id', 'votes.candidate_id');
            })
            ->delete();

        DB::table('votes')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('positions')
                    ->whereColumn('positions.id', 'votes.position_id');
            })
            ->delete();

        DB::table('votes')
            ->whereNotNull('election_id')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('elections')
                    ->whereColumn('elections.id', 'votes.election_id');
            })
            ->delete();

        DB::table('votes')
            ->join('voters', 'voters.id', '=', 'votes.voter_id')
            ->join('elections', 'elections.id', '=', 'votes.election_id')
            ->select('votes.voter_id', 'votes.election_id', DB::raw('MIN(votes.created_at) as participated_at'))
            ->whereNotNull('election_id')
            ->groupBy('votes.voter_id', 'votes.election_id')
            ->orderBy('votes.voter_id')
            ->lazy()
            ->each(function ($row) {
                DB::table('voter_election_participations')->updateOrInsert(
                    [
                        'voter_id' => $row->voter_id,
                        'election_id' => $row->election_id,
                    ],
                    [
                        'participated_at' => $row->participated_at ?? now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            });
    }

    public function down(): void
    {
        //
    }
};
