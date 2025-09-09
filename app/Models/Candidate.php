<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Candidate extends Model
{
    protected $fillable = [
        'voter_id',
        'position_id',
        'election_id',
        'course_id',
        'department_id',
        'slogan',
        'photo_path',
    ];

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
    
    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }
}
