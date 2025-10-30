<?php

namespace App\Models;

use App\Models\Outcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'projectCode',
        'projectTitle',
        'projectGoal',
        'projectDonor',
        'startDate',
        'endDate',
        'status',
        'projectManager',
        'reportingDate',
        'reportingPeriod',
        'description',
    ];

    protected $casts = [
        'startDate' => 'datetime',
        'endDate' => 'datetime',
    ];

    public function outcomes()
    {
        return $this->hasMany(Outcome::class);
    }

    public function outputs() 
    {
        return $this->hasManyThrough(Output::class, Outcome::class);
    }

    public function indicators()
    {
        return $this->hasManyThrough(
            Indicator::class,
            Output::class,
            'outcome_id',
            'output_id',
            'id',
            'id'
        );
    }

    public function programs () 
    {
        return $this->hasMany(Program::class);
    }

    public function provinces()
    {
        return $this->belongsToMany(Province::class);
    }

    public function databases () 
    {
        return $this->hasManyThrough(Database::class, Program::class);
    }

    public function sectors()
    {
        return $this->belongsToMany(Sector::class);
    }

    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function logs ()
    {
        return $this->hasMany(ProjectLogs::class);
    }

    public function psychoeducations ()
    {
        return $this->hasManyThrough(Psychoeducations::class, Program::class);
    }

    public function isp3s ()
    {
        
    }

}
