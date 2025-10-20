<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommunityDialogue extends Model
{
    protected $fillable = [
        "program_id",
        "indicator_id",
        "remark"
    ];


    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function groups ()
    {
        return $this->hasMany(Group::class);
    }

    public function sessions ()
    {
        return $this->hasMany(CommunityDialogueSession::class);
    }

    public function beneficiaries ()
    {
        return $this->belongsToMany(Beneficiary::class);
    }

    public function program ()
    {
        return $this->belongsTo(Program::class);
    }

    public function indicator ()
    {
        return $this->belongsTo(Indicator::class);
    }
}
