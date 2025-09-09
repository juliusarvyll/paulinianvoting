<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('positions', 'election_id')) {
            Schema::table('positions', function (Blueprint $table) {
                $table->foreignId('election_id')
                    ->after('id')
                    ->constrained()
                    ->onDelete('cascade');
                $table->index(['election_id', 'level']);
            });
        }
    }

    public function down(): void
    {
    }
};
