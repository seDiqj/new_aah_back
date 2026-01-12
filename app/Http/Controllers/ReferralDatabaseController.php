<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\Referral;
use App\Models\Indicator;
use App\Models\Database;
use Illuminate\Http\Request;

class ReferralDatabaseController extends Controller
{
    public function index (Request $request)
    {
        $query = Beneficiary::query()->whereHas("referral")->with("programs");

        if ($request->filled("age")) 
            $query->where("age", "like", "%$request->age%");

        if ($request->filled("gender")) 
            $query->where("gender", "like", "%$request->gender%");

        if ($request->filled("dateOfRegistration"))
            $query->where("dateOfRegistration", "like", "%$request->dateOfRegistration%");

        if ($request->filled("projectCode"))
            $query->whereHas("programs", function ($q) use ($request) {
                $q->whereHas("project", function ($q2) use ($request) {
                    $q2->where("projectCode", "like", "%$request->projectCode%");
                });
            });

        if ($request->filled("province"))
            $query->whereHas("programs", function ($q) use ($request) {
                $q->whereHas("province", function ($q2) use ($request) {
                    $q2->where("name", "like", "%$request->province%");
                });
            });

        if ($search = request("search")) {
            $query->where("name", "like", "%$search%");
        }

        if ($request->filled('code')) {
            $query->where("code", "like", "%" . $request->code . "%");
        }

        $beneficiaries = $query->paginate(10);

        if ($beneficiaries->isEmpty()) return response()->json(["status" => false, "message" => "No beneficiary was found !", "data" => []], 200);


        $beneficiaries->getCollection()->transform(function ($bnf) {
            $bnf->programName = optional($bnf->programs->first())->name;
            unset($bnf->programs);
            return $bnf;
        });

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries]);
    }

    public function indexRefferalDatabaseIndicators ()
    {

        $refferalDatabase = Database::where("name", "refferal_database")->first();

        if (!$refferalDatabase) return response()->json(["status" => false, "message" => "Refferal database is not a valid database !", "data" => []], 404);

        $indicators = Indicator::where("database_id", $refferalDatabase->id)->get();

        if ($indicators->isEmpty())
            return response()->json(["status" => false, "message" => "There is no indicator that belongs to refferal database !", "data" => []], 404);


        $finalData = $indicators->map(function ($indicator) {
            return [
                "id" => $indicator->id,
                "indicatorRef" => $indicator->indicatorRef,
            ];
        });

        return response()->json(["status" => true, "message" => "", "data" => $finalData], 200);

    }

    public function refferBeneficiaries (Request $request)
    {

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer|exists:beneficiaries,id",
            "selectedIndicator" => "required|integer|exists:indicators,id"
        ]);
        
        $ids = $request->input("ids");

        $beneficiaries = Beneficiary::whereIn("id", $ids)->get();

        foreach ($beneficiaries as $beneficiary) {
            $beneficiary->referral()->updateOrCreate([
                "indicator_id" => $request->input("selectedIndicator")
            ]);
        }

        return response()->json(["status" => true, "message" => (string) count($beneficiaries) . " added to referral !"], 200);

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
