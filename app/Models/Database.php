<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Database extends Model
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
