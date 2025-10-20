<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Referral;
use Illuminate\Http\Request;

class ReferralDatabaseController extends Controller
{
    public function index ()
    {
        $beneficiariesIds = Referral::all()->pluck("beneficiary_id")->toArray();

        if (count($beneficiariesIds) == 0) return response()->json(["status" => false, "message" => "No beneficiary was found in referral database !"], 404);

        $beneficiaries = Beneficiary::whereIn("id", $beneficiariesIds)->get();

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries]);
    }

    public function show (string $id)
    {
        $beneficiary = Beneficiary::with("referral")->select("id", "name", "fatherHusbandName", "phone", "dateOfRegistration", "childAge", "childCode", "code", "disabilityType", "gender", "age", "householdStatus", "literacyLevel", "maritalStatus")->find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $beneficiary]);
    }

    public function update (Request $request, string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $data = $request->all();

        $beneficiary->referral()->update($data);

        return response()->json(["status" => true, "message" => "Beneficiary referral form updated successfully !"], 200);
    }

    public function referrBeneficiaries(Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        $beneficiaries = Beneficiary::whereIn("id", $ids)->get();

        foreach ($beneficiaries as $beneficiary) {
            $beneficiary->referral()->updateOrCreate([]);
        }

        return response()->json(["status" => true, "message" => (string) count($beneficiaries) . " added to referral !"], 200);
    }

    public function destroy (Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        Referral::whereIn("beneficiary_id", $ids)->delete();

        return response()->json(["stataus" => false, "message" => (string) count($ids) . " Beneficiaries successfully removed from referral list !"], 200);
    }
}
