<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;


class Question extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'group',
        'description',
    ];

    public function assessments()
    {
        return $this->belongsToMany(Assessment::class, 'assessment_question')
            ->withPivot('score')
            ->withTimestamps();
    }
}