<?php

namespace App\Models;

use App\Models\BaseModel;

class Isp3 extends BaseModel
{
    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function indicators ()
    {
        return $this->belongsToMany(Indicator::class);
    }
}
