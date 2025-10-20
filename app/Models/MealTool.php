<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealTool extends Model
{
    protected $fillable = [
        "beneficiary_id",
        "type",
        "baselineDate",
        "endlineDate",
        "baselineTotalScore",
        "endlineTotalScore",
        "improvementPercentage",
        "baseline",
        "endline",
        "isBaselineActive",
        "isEndlineActive",
        "evaluation",
    ];


    public function beneficiary ()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
