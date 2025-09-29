<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('votes', 'election_id')) {
            Schema::table('votes', function (Blueprint $table) {
                $table->foreignId('election_id')
                    ->after('id')
                    ->constrained()
                    ->onDelete('cascade');
                $table->index(['election_id', 'position_id', 'candidate_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['election_id', 'position_id', 'candidate_id']);
            $table->dropConstrainedForeignId('election_id');
        });
    }
};
