<?php

namespace App\Models;

use App\Models\BaseModel;

class Database extends BaseModel
{

    protected $fillable = [
        "name"
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function beneficiaries ()
    {
        return $this->belongsToMany(Beneficiary::class, "database_program_beneficiary")
                        ->withPivot("program_id");
    }

    public function indicators ()
    {
        return $this->hasMany(Indicator::class);
    }

    public function programs ()
    {
        return $this->hasMany(Program::class);
    }
}
