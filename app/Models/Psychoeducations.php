<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Psychoeducations extends Model
{
    protected $fillable = [
        "program_id",
        "indicator_id",
        "awarenessTopic",
        "awarenessDate",
        // men
        "ofMenHostCommunity",
        "ofMenIdp",
        "ofMenRefugee",
        "ofMenReturnee",
        "ofMenDisabilityType",
        // women
        "ofWomenHostCommunity",
        "ofWomenIdp",
        "ofWomenRefugee",
        "ofWomenReturnee",
        "ofWomenDisabilityType",
        // boy
        "ofBoyHostCommunity",
        "ofBoyIdp",
        "ofBoyRefugee",
        "ofBoyReturnee",
        "ofBoyDisabilityType",
        // girl
        "ofGirlHostCommunity",
        "ofGirlIdp",
        "ofGirlRefugee",
        "ofGirlReturnee",
        "ofGirlDisabilityType",
        "remark",
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function program ()
    {
        return $this->belongsTo(Program::class);
    }
}

