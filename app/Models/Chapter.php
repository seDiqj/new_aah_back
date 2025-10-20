<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chapter extends Model
{
    protected $fillable = [
        "training_id",
        "topic",
        "facilitatorName",
        "facilitatorJobTitle",
        "startDate",
        "endDate",
    ];

    public function training ()
    {
        return $this->belongsTo(Training::class);
    }

    public function beneficiaries ()
    {
        return $this->belongsToMany(Beneficiary::class)
                        ->withPivot("isPresent", "preTestScore", "postTestScore")
                        ->withTimestamps();

    }
}
