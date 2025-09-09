<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            // Add a unique index to prevent duplicate votes per voter/election/position
            $table->unique(['voter_id', 'election_id', 'position_id'], 'uniq_vote_voter_election_position');
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropUnique('uniq_vote_voter_election_position');
        });
    }
};
