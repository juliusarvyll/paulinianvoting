<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'department_name',
        'logo_path',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function getLogoPathAttribute($value)
    {
        return $value ? asset('storage/' . $value) : null;
    }
}
