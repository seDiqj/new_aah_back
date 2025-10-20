<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Isp3 extends Model
{
    protected $fillable = [
        "indicator_id",
        "isp3_id"
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function indicators ()
    {
        return $this->belongsToMany(Indicator::class);
    }
}
