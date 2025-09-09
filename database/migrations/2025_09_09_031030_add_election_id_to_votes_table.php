<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->foreignId('election_id')
                ->nullable()
                ->after('position_id')
                ->constrained()
                ->onDelete('cascade');
            $table->index(['voter_id', 'election_id']);
        });
    }

    public function down(): void
    {
        Schema::table('votes', function (Blueprint $table) {
            $table->dropIndex(['voter_id', 'election_id']);
            $table->dropConstrainedForeignId('election_id');
        });
    }
};
