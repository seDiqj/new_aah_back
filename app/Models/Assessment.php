<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enact_id',
        'totalScore',
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function enact()
    {
        return $this->belongsTo(Enact::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'assessment_question')
                    ->withPivot('score')
                    ->withTimestamps();
    }
}
