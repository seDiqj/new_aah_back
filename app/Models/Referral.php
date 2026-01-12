<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referral extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'indicator_id',
        'referralConcern',
        'referralConcernNote',
        'concentGiven',
        'needReferral',
        'problemReportedBy',
        'caseNumber',
        'type',
        'referrerName',
        'referrerAgency',
        'referrerPosition',
        'referrerPhone',
        'referrerEmail',
        'referredToName',
        'referredToAgency',
        'referredToPosition',
        'referredToPhone',
        'referredToEmail',
        'clientDob',
        'currentAddress',
        'spokenLanguage',
        'referralReason',
        'mentalHealthAlert',
        'mentalHealthDesk',
        'serviceRequested',
        'expectedOutcome',
        'referralAccepted',
        'referralRejectedReasone',
    ];

    protected $casts = [
        'referralConcern'   => 'boolean',
        'concentGiven'      => 'boolean',
        'needReferral'      => 'boolean',
        'referralAccepted'  => 'boolean',
        'clientDob'         => 'date',
        'spokenLanguage'    => 'array',
        'mentalHealthAlert' => 'array',
        'serviceRequested'  => 'array',
    ];

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function indicator ()
    {
        return $this->belongsTo(Indicator::class);
    }
}
