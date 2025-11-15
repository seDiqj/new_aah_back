<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Http\Requests\StoreKitDistributionRequest;
use App\Http\Requests\StoreKitForBeneficiaryRequest;
use App\Models\Beneficiary;
use App\Models\Database;
use App\Models\District;
use App\Models\Indicator;
use App\Models\Kit;
use App\Models\KitDistribution;
use App\Models\Program;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Http\Request;

class KitDatabaseController extends Controller
{
    public function indexBeneficiaries() {

        $kitDb = Database::where("name", "kit_database")->first();

        if (!$kitDb) return response()->json(["status" => false, "message" => "Kit Database is not in system"]);

        $kitDbId = $kitDb->id;

        $beneficiaries = Beneficiary::whereHas("programs", function ($query) use ($kitDbId) {
            $query->where("database_program_beneficiary.database_id", $kitDbId);
        })->with(["programs" => function ($query) use ($kitDbId) {
            $query->where("database_program_beneficiary.database_id", $kitDbId)
                  ->select("programs.id", "focalPoint");
        }])->get();
        
        $beneficiaries = $beneficiaries->map(function ($beneficiary) {
            $beneficiary->programs = $beneficiary->programs->map(function ($program) {
                return [
                    'id' => $program->id,
                    'focalPoint' => $program->focalPoint,
                    'pivot' => $program->pivot
                ];
            });
            return $beneficiary;
        });
        

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries]);

    }

    public function indexBeneficiaryKitList(string $id) {

        $beneficiary = Beneficiary::with("kits")->where("id", $id)->first();

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such user in system !"], 404);

        $kits = $beneficiary->kits;

        if ($kits->isEmpty()) return response()->json(["status" => true, "message" => "No kit found for selected beneficiary !"], 404);

        $manipulatedKits = $kits->map(function ($kit) {
            return [
                "id" => $kit->id,
                "kit" => $kit->name,
                "distributionDate" => $kit->pivot->destribution_date,
                "isReceived" => $kit->pivot->is_received == 1 ? "Yes" : "No",
                "remark" => $kit->pivot->remark
            ];
        });

        return response()->json(["status" => true, "message" => "", "data" => $manipulatedKits]);

    }

    public function indexKitList () 
    {
        $kits = Kit::select("id", "name")->get();

        if ($kits->isEmpty()) return response()->json(["status" => false, "message" => "No kit in system !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $kits]);
    }
    
    public function addNewKitToBeneficiary (StoreKitForBeneficiaryRequest $request, string $id)
    {
        
        $validated = $request->validated();

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $kitsIds = Kit::whereIn("name", $request->input("kits"))->pluck("id")->toArray();

        if (count($kitsIds) != count($request->input("kits"))) return response()->json(["status" => false, "message" => "Invalid kit selected !"], 422);

        foreach($kitsIds as $kitId) {
            $beneficiary->kits()->attach($kitId, [
                "destribution_date" => $validated["distributionDate"],
                "remark" => $validated["remark"],
                "is_received" => $validated["isReceived"]
            ]);
        }

        return response()->json(["status" => true, "message" => "Kit successfully added !"], 200);
        
    }

    public function storeBeneficiary(StoreBeneficiaryRequest $request) 
    {

        if (!($request->input("program") || $request->input("indicators"))) 
            return response()->json(["status" => false, "message" => "Please select a valid indicator / program", "data" => $request->all()], 422);

        $indicators = $request->input("indicators");

        $program = Program::where("focalPoint", $request->input("program"))->first();

        if (!$program) return response()->json(["status" => false, "message" => "Invalid program selected !"], 422);

        $indicatorIds = Indicator::whereIn("indicator", $indicators)->pluck("id")->toArray();

        $validated = $request->validated();

        $validated["protectionServices"] = null;

        $validated["aprIncluded"] = true;

        $beneficiary = Beneficiary::create($validated);

        $beneficiary->indicators()->sync($indicatorIds);

        $kitDbId = Database::where("name", "kit_database")->first()->id;

        $beneficiary->programs()->attach($program->id, [
            "database_id" => $kitDbId
        ]);

        return response()->json(["status" => true, "message" => "Beneficiary successfully created !"], 200);

    }

    public function storeBeneficiaryKit(StoreKitDistributionRequest $request) {

        $validated = $request->validated();

        $distribution = KitDistribution::create($validated);

        if (!$distribution->exists) return response()->json(["status" => false, "message" => "Somthing gone wrong !"], 500);

        return response()->json(["status" => true, "message" => "Kit successfully added !"], 200);

    }

    public function showBeneficiary(string $id)
    {
        $beneficiary = Beneficiary::with(['indicators', 'programs'])
            ->select(
                "id",
                "name",
                "dateOfRegistration",
                "code",
                "fatherHusbandName",
                "age",
                "gender",
                "maritalStatus",
                "childCode",
                "childAge",
                "phone",
                "literacyLevel",
                "householdStatus",
                "disabilityType",
                "protectionServices"
            )
            ->find($id);

        if (!$beneficiary) {
            return response()->json([
                "status" => false,
                "message" => "No such beneficiary in system !"
            ], 404);
        }

        $data = $beneficiary->toArray();

        // indicators به آرایه ساده تبدیل می‌شوند
        $data['indicators'] = $beneficiary->indicators->pluck('indicator')->toArray();

        // programs هم به آرایه ساده با id و name
        $data['programs'] = $beneficiary->programs->map(function($program) {
            return [
                'id' => $program->id,
                'name' => $program->name,
            ];
        })->toArray();

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $data
        ]);
    }

    public function showBeneficiaryProgram(string $id) {

        $beneficiary = Beneficiary::with("programs")->find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        if ($beneficiary->programs->isEmpty()) return response()->json(["status" => false, "message" => "No program was found for this beneficiary !"], 404);

        $programs = $beneficiary->programs->map(function ($program) {
            return [
                "projectCode" => Project::find($program->project_id)->projectCode,
                "focalPoint" => $program->focalPoint,
                "province" => Province::where("id", $program->province_id)->first()->name,
                "district" => District::where("id", $program->district_id)->first()->name,
                "village" => $program->village,
                "siteCode" => $program->siteCode,
                "healthFacilityName" => $program->healthFacilityName,
                "interventionModality" => $program->interventionModality
            ];
        });

        return response()->json(["status" => true, "message" => "", "data" => $programs]);

    }

    public function showKit (string $id)
    {
        $kit = KitDistribution::find($id);

        if (!$kit) return response()->json(["status" => false, "message" => "No such kit in system !"], 404);

        return response()->json(["status" => false, "message" => "", "data" => $kit], 200);
    }

    public function updateBeneficiary(Request $request, string $id) {

        $kitDatabaseFromDb = Database::where("name", "kit_database")->first();

        if (!$kitDatabaseFromDb) return response()->json(["status" => false, "message" => "Kit database is not a valid database in system !"], 404);

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $beneficiary->update($request->input("bnfData"));

        $beneficiary->indicators()->sync($request->input("indicators"));

        $beneficiary->programs()->sync([$request->input("program") => ["database_id" => $kitDatabaseFromDb->id]]);

        return response()->json(["status" => true, "message" => "Beneficiary successfully updated !"], 200);

    }

    public function updateKit(Request $request, string $id) {

        $kit = KitDistribution::find($id);

        if (!$kit) return response()->json(["status" => false, "message" => "No such kit in system !"], 404);

        $validated = $request->validate([
            "distribution_date" => "required|date",
            "remark" => "required|string",
            "is_received" => "required|boolean"
        ]);

        $kit->update($validated);

        return response()->json(["status" => true, "message" => "Kit successfully updated !"], 200);

    }

    public function destroyBeneficiary(Request $request) {

        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        $isMoreThenOne = count($ids) > 1 ? true : false;

        Beneficiary::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => $isMoreThenOne ? "Beneficiaries " : "Beneficiary " . "successfully deleted !"], 200);

    }

    public function destroyKits(Request $request) {

        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        $isMoreThenOne = count($ids) > 1 ? true : false;

        KitDistribution::whereIn("id", $ids)->delete();


        return response()->json(["status" => true, "message" => $isMoreThenOne ? "Kits " : "Kit " . "successfully deleted !"], 200);

    }
}
