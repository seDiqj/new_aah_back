<?php

namespace App\Models;

use App\Models\Outcome;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Support\Facades\Log;

class Project extends BaseModel
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
        'startDate' => 'datetime:F d, Y',
        'endDate' => 'datetime:F d, Y',
    ];

    protected static function booted()
    {
        parent::booted();

        static::deleting(function ($project) {
            $project->programs()->select('id')->chunk(100, function ($programs) use ($project) {
                foreach ($programs as $programRow) {
                    $program = \App\Models\Program::find($programRow->id);
                    if (!$program) continue;

                    if ($project->isForceDeleting()) {
                        $program->forceDelete();
                        Log::info("ForceDeleted program id={$program->id}");
                    } else {
                        $program->delete();
                        Log::info("SoftDeleted program id={$program->id}");
                    }
                }
            });
        });
    }

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

}
