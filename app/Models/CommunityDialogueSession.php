<?php

namespace App\Models;

use App\Models\BaseModel;

class CommunityDialogueSession extends BaseModel
{
    protected $fillable = [
        "community_dialogue_id",
        "type",
        "topic",
        "date"
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    public function beneficiaries()
    {
        return $this->belongsToMany(
            Beneficiary::class,
            'beneficiary_community_dialogue_session',
            'community_dialogue_session_id',
            'beneficiary_id'
        );
    }
}
