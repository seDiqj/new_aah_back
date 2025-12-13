<?php

namespace App\Models;

use App\Models\BaseModel;

class Program extends BaseModel
{
    
    protected $fillable = [
        'database_id',
        'name',
        'project_id',
        'focalPoint',
        'province_id',
        'district_id',
        'village',
        'siteCode',
        'healthFacilityName',
        'interventionModality',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function database()
    {
        return $this->belongsTo(Database::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function beneficiaries ()
    {
        return $this->belongsToMany(Beneficiary::class, "database_program_beneficiary")
                        ->withPivot("database_id");
    }

    public function psychoeducation ()
    {
        return $this->hasOne(Psychoeducations::class);
    }
}
