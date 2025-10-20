<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $fillable = [
        "name"
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function project ()
    {
        return $this->belongsTo(Project::class);
    }

    public function indicators()
    {
        return $this->belongsToMany(Indicator::class)
                        ->withPivot(["target", "councilorCount"])
                        ->withTimestamps();
    }
}
