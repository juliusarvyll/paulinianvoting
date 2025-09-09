<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voter_election_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voter_id')->constrained()->onDelete('cascade');
            $table->foreignId('election_id')->constrained()->onDelete('cascade');
            $table->timestamp('participated_at')->useCurrent();
            $table->timestamps();
            $table->unique(['voter_id', 'election_id'], 'uniq_voter_election');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_election_participations');
    }
};
