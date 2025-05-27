<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Voter extends Model
{
    protected $fillable = [
        'code',
        'last_name',
        'first_name',
        'middle_name',
        'sex',
        'department_id',
        'course_id',
        'year_level',
        'has_voted',
    ];

    protected $casts = [
        'has_voted' => 'boolean',
    ];

    protected $appends = ['name'];

    /**
     * Get the voter's full name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_name . ', ' . $this->first_name . ' ' . $this->middle_name,
        );
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function candidate(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
