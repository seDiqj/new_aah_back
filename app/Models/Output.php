<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Output extends BaseModel
{
    use HasFactory;

    protected $fillable = 
    [
        'output',
        'outputRef',
        'outcome_id',
    ];

    public function outcome()
    {
        return $this->belongsTo(Outcome::class);
    }

    public function project()
    {
        return $this->outcome ? $this->outcome->project() : null;
    }

    public function indicators() 
    {
        return $this->hasMany(Indicator::class);
    }
}
