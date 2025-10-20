<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $fillable = [
        'project_id',
        'province_id',
        'district_id',
        'indicator_id',
        'trainingLocation',
        'name',
        'participantCatagory',
        'aprIncluded',
        'trainingModality',
        'startDate',
        'endDate',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function chapters ()
    {
        return $this->hasMany(Chapter::class);
    }

    public function beneficiaries ()
    {
        return $this->belongsToMany(Beneficiary::class);
    }

    public function evaluations ()
    {
        return $this->hasOne(TrainingEvaluation::class);
    }
}
