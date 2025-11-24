<?php

namespace App\Models;

use App\Models\BaseModel;

class Kit extends BaseModel
{    
    protected $fillable = [

        "name",
        "description",
        "status"

    ];
}
