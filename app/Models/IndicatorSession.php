<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndicatorSession extends Model
{
    protected $fillable = [
        "indicator_id",
        "beneficiary_id",
        "group",
        "topic",
        "session",
        "date"
    ];


    protected $hidden = [
        "beneficiary_id",
        "indicator_id",
        "created_at",
        "updated_at",
    ];


    public function indicator ()
    {
        return $this->belongsTo(Indicator::class);
    }

    public function beneficiaries ()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
