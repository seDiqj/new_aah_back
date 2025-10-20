<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indicator extends Model
{
    use HasFactory;

    protected $fillable = [
        'output_id',
        'parent_indicator',
        'database_id',
        'type_id',
        'indicator',
        'indicatorRef',
        'target',
        'achived_target',
        'status',
        'dessaggregationType',
        'description',
    ];


    protected $casts = [

        'monthly_counts' => 'array',

    ];

    public function beneficiaries()
    {
        return $this->belongsToMany(Beneficiary::class, "beneficiary_indicator");
    }
    
    public function output()
    {
        return $this->belongsTo(Output::class);
    }

    /**
     * Sub-indicators
     */
    public function subIndicator()
    {
        return $this->hasMany(Indicator::class, 'parent_indicator');
    }

    /**
     * Parent indicator
     */
    public function parent()
    {
        return $this->belongsTo(Indicator::class, 'parent_indicator');
    }

    public function dessaggregations()
    {
        return $this->hasMany(Dessaggregation::class);
    }

    public function provinces() 
    {
        return $this->belongsToMany(Province::class)
                        ->withPivot(["target", "councilorCount"])
                        ->withTimestamps();
    }

    public function database()
    {
        return $this->belongsTo(Database::class);
    }

    public function sessions ()
    {
        return $this->hasMany(IndicatorSession::class);
    }

    public function isp3 ()
    {
        return $this->belongsToMany(Isp3::class);
    }
}

