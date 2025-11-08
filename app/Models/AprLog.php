<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Apr;


class AprLog extends Model
{
    protected $fillable = [
        'apr_id',
        'user_id',
        'action',
        'comment'
    ];

    public function apr ()
    {
        return $this->belongsTo(Apr::class);
    }

    public function user () 
    {
        return $this->belongsTo(User::class);
    }
}
