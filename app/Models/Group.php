<?php

namespace App\Models;

use App\Models\BaseModel;

class Group extends BaseModel
{

    protected $fillable = [
        "community_dialogue_id",
        "name"
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];


    public function communityDialogue ()
    {
        return $this->belongsTo(CommunityDialogue::class);
    }

    public function beneficiaries ()
    {
        return $this->belongsToMany(Beneficiary::class);
    }
}
