<?php

namespace App\Models;
use App\Models\BaseModel;


class Apr extends BaseModel
{    
    protected $fillable = [
        "project_id",
        "database_id",
        "province_id",
        "status",
        "fromDate",
        "toDate"
    ];


    protected $hidden = [
        "created_at",
        "updated_at"
    ];


    public function project ()
    {
        return $this->belongsTo(Project::class);
    }

    public function database ()
    {
        return $this->belongsTo(Database::class);
    }

    public function province ()
    {
        $this->belongsTo(Province::class);
    }
}
