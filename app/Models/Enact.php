<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enact extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'province_id',
        'indicator_id',
        'councilorName',
        'raterName',
        'type',
        'date',
        'aprIncluded',
    ];

    protected $hidden = [
        "created_at",
        "updated_at"
    ];

    protected $casts = [
        "aprIncluded" => "boolean"
    ];

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function indicator()
    {
        return $this->belongsTo(Indicator::class);
    }
}