<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;


class Outcome extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'outcome',
        'outcomeRef',
        'project_id',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function outputs()
    {
        return $this->hasMany(Output::class);
    }
}
