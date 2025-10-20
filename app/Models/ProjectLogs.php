<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectLogs extends Model
{
    protected $fillable = [
        "project_id",
        "user_id",
        "action",
        "comment",
        "result"
    ];

    protected $hidden = [
        "updated_at",
    ];


    public function project ()
    {
        return $this->belongsTo(Project::class);
    }

    public function user ()
    {
        return $this->belongsTo(User::class);
    }
}
