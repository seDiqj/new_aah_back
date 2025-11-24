<?php

namespace App\Models;

use App\Models\BaseModel;

class Chapter extends BaseModel
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
