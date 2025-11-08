<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
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
