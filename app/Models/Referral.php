<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Referral extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
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

    /**
     * هر Referral به یک Beneficiary تعلق دارد
     */
    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
