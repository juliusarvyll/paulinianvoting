<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Candidate;

class Election extends Model
{
    protected $fillable = [
        'name',
        'start_at',
        'end_at',
        'is_active',
    ];

    protected $casts = [ 
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Election $election) {
            // If this election is being activated, deactivate all others
            if ($election->is_active) {
                static::where('id', '!=', $election->id)->update(['is_active' => false]);
            }
        });
    }

    // Scope to get the single active election
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
