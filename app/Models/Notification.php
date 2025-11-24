<?php

namespace App\Models;

use App\Models\BaseModel;

class Notification extends BaseModel
{    
    protected $fillable = [
        "title",
        "message",
        "database_id",
        "project_id",
        "apr_id",
        "type",
    ];

    public function users ()
    {
        return $this->belongsToMany(User::class);
    }
}
