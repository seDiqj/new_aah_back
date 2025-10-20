<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
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