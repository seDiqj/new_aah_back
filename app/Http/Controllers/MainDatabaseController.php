<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBeneficiaryRequest;
use App\Models\Beneficiary;
use App\Models\Database;
use App\Models\District;
use App\Models\Indicator;
use App\Models\IndicatorSession;
use App\Models\IndicatorType;
use App\Models\KitDistribution;
use App\Models\MealTool;
use App\Models\Program;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Http\Request;

class MainDatabaseController extends Controller
{
    public function indexBeneficiaries(Request $request) 
    {

        $mainDb = Database::where("name", "main_database")->first();

        if (!$mainDb) return response()->json(["status" => false, "message" => "Kit Database is not in system"]);

        $mainDbId = $mainDb->id;

        $query = Beneficiary::query()
            ->with(['programs.project', 'indicators', 'mealTools']);

        if ($search = request("search")) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $query->whereHas('programs', function($q) use ($request, $mainDbId) {
            $q->where('database_program_beneficiary.database_id', $mainDbId);

            if ($request->filled('projectCode')) {
                $q->whereHas('project', function($q2) use ($request) {
                    $q2->where('projectCode', 'like', '%' . $request->projectCode . '%');
                });
            }

            if ($request->filled('focalPoint')) {
                $q->where('focalPoint', $request->focalPoint);
            }

            if ($request->filled('province')) {
                $q->whereHas("province", function ($q3) use ($request) {
                    $q3->where("name", "like", "%" . $request->province . "%");
                });
            }

            if ($request->filled('siteCode')) {
                $q->where('siteCode', $request->siteCode);
            }

            if ($request->filled('healthFacilitator')) {
                $q->where('healthFacilityName', 'like', '%' . $request->healthFacilitator . '%');
            }
        });

        if ($request->filled('dateOfRegistration')) {
            $query->where('dateOfRegistration', $request->dateOfRegistration);
        }

        if ($request->filled('age')) {
            $query->where('age', $request->age);
        }

        if ($request->filled('maritalStatus')) {
            $query->where('maritalStatus', $request->maritalStatus);
        }

        if ($request->filled('householdStatus')) {
            $query->where('householdStatus', 'like', '%' . $request->householdStatus . '%');
        }

        if ($request->filled('baselineDate')) {
            $query->whereHas('mealTools', function($q) use ($request) {
                $q->where('baselineDate', $request->baselineDate);
            });
        }

        if ($request->filled('endlineDate')) {
            $query->whereHas('mealTools', function($q) use ($request) {
                $q->where('endlineDate', $request->endlineDate);
            });
        }

        if ($request->filled('indicator')) {
            $query->whereHas('indicators', function($q) use ($request) {
                $q->where('indicator', 'like', '%' . $request->indicator . '%');
            });
        }

        if ($request->filled('code')) {
            $query->where("code", "like", "%" . $request->code . "%");
        }


        $beneficiaries = $query->paginate(10);

        if ($beneficiaries->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No beneficiary was found !",
                "data" => [],
            ], 200);
        }

        $beneficiaries->getCollection()->transform(function ($bnf) {
            $bnf->programName = optional($bnf->programs->first())->name;
            unset($bnf->programs);
            return $bnf;
        });

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries]);
    }

    public function indexBeneficiaryMealtools(string $id) 
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $mealtools = $beneficiary->mealTools;

        if ($mealtools->isEmpty()) return response()->json(["status" => false, "message" => "No mealtools found for current beneficiary !", "data" => []], 200);

        $mealtools = $mealtools->map(function ($mealtool) {
            if ($mealtool->isBaselineActive) 
                $mealtool->isBaselineActive = true;
            else $mealtool->isBaselineActive = false;

            if ($mealtool->isEndlineActive)
                $mealtool->isEndlineActive = true;
            else $mealtool->isEndlineActive = false;

            return $mealtool;
        });

        return response()->json(["status" => true, "message" => "", "data" => $mealtools]);
    }

    public function indexIndicators(string $id)
    {
        $mainDatabase = Database::where("name", "main_database")->first();

        if (!$mainDatabase) {
            return response()->json([
                "status" => false,
                "message" => "Main Database is not a valid database !"
            ], 404);
        }

        $beneficiaryProgram = Program::whereHas("beneficiaries", function ($query) use ($id) {
            $query->where("beneficiary_id", $id);
        })
        ->with('project.outcomes.outputs.indicators')
        ->first();

        if (!$beneficiaryProgram) {
            return response()->json([
                "status" => false,
                "message" => "The beneficiary does not belong to any of our valid programs !"
            ], 404);
        }

        $project = $beneficiaryProgram->project;

        if (!$project) {
            return response()->json([
                "status" => false,
                "message" => "The program does not have a valid linked project !"
            ], 404);
        }

        $programIndicators = $project->outcomes
            ->flatMap(fn($outcome) => $outcome->outputs)
            ->flatMap(fn($output) => 
                $output->indicators()
                    ->where("database_id", $mainDatabase->id)
                    ->with([
                        "sessions" => function ($query) use ($id) {
                            $query->where("beneficiary_id", $id);
                        },
                        "dessaggregations"
                    ])
                    ->get()
            )
            ->transform(function ($ind) {
                $type = IndicatorType::find($ind["type_id"]);
                $ind["type"] = $type?->type ?? null;
                unset($ind["type_id"], $ind["created_at"], $ind["updated_at"]);
                return $ind;
            })
            ->values();

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $programIndicators
        ]);
    }

    public function storeSessions (Request $request, string $id)
    {

        $indicators = $request->input("indicators");

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !", "data" => []], 404);

        foreach ($indicators as $indicator) {

            $indicatorFromDb = Indicator::find($indicator["id"]);

            if (!$indicatorFromDb) return response()->json(["status" => false, "message" => "The indicator with referance " . $indicator["indicatorRef"] . " is not a valid indicator"], 404);

            foreach ($indicator["sessions"] as $session) {

                $indicatorFromDb->sessions()->updateOrCreate([
                    "id" => $session["id"]
                ], [
                    "beneficiary_id" => $id,
                    "group" => $session["group"],
                    "session" => $session["session"],
                    "date" => $session["date"],
                    "topic" => $session["topic"],
                ]);

            }

            if (count($indicator["sessions"]) >= 1) {
                $beneficiary->indicators()->syncWithoutDetaching([$indicator["id"]]);

                $subIndicator = Indicator::where("parent_indicator", $indicator["id"])->first();

                if ($subIndicator)
                    $beneficiary->indicators()->syncWithoutDetaching([$subIndicator->id]);
            }

            

        }

        return response()->json(["status" => true, "message" => "Sessions successfully added to beneficiary !"], 200);

    }

    public function storeBeneficiary(StoreBeneficiaryRequest $request) 
    {

        $validated = $request->validated();

        $validated["aprIncluded"] = true;

        $beneficiary = Beneficiary::create($validated);

        $mainDbId = Database::where("name", "main_database")->first()->id;

        $beneficiary->programs()->attach($request->input("program"), [
            "database_id" => $mainDbId
        ]);

        return response()->json(["status" => true, "message" => "Beneficiary successfully created !"], 200);

    }

    public function storeMealtool(Request $request, string $id) 
    {

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $mealtool = $request->input("mealtool");

        $createdMealtool = $beneficiary->mealTools()->create($mealtool);

        return response()->json(["status" => true, "message" => "Mealtools successfully assigned to beneficiary !", "data" => $createdMealtool], 200);
    }

    public function storeBeneficiaryEvaluation (Request $request, string $id)
    {

        $data = $request->input('evaluation');

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $beneficiary->evaluation()->updateOrCreate(
            ['beneficiary_id' => $beneficiary->id], 
            $data                                   
        );

        return response()->json(["status" => true, "message" => "Evaluation successfully saved !"], 200);
    }

    public function showBeneficiary(string $id) 
    {

        $beneficiary = Beneficiary::select("name", "dateOfRegistration", "code", "fatherHusbandName", "age", "gender", "maritalStatus", "childCode", "childAge", "phone", "literacyLevel", "householdStatus")->find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $beneficiary]);

    }

    public function showBeneficiaryProgram (string $id)
    {
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

    public function showBeneficiaryEvaluation(string $id) {

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $evaluation = $beneficiary->evaluation;

        if (!$evaluation) return response()->json(["status" => false, "message" => "No evaluation was found for current beneficiary !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $evaluation]);

    }

    public function showMealtool (string $id) {

        $mealTool = MealTool::find($id);

        if (!$mealTool) return response()->json(["status" => false, "message" => "No such mealtool in system !", "data" => []], 404);

        return response()->json(["status" => true, "message" => "", "data" => $mealTool], 200);

    }

    public function updateBeneficiary(Request $request, string $id) {

        $mainDatabaseFromDb = Database::where("name", "main_database")->first();

        if (!$mainDatabaseFromDb) return response()->json(["status" => false, "message" => "No such database in system named Main Database !"], 404);

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["statue" => false, "message" => "No such beneficiary in system !"], 404);

        $beneficiary->programs()->sync([
            $request->input("program") => [
                "database_id" => $mainDatabaseFromDb->id
            ],
            
        ]);

        $beneficiary->update($request->bnfData);

        return response()->json(["status" => true, "message" => "Beneficiary successfully updated !"], 200);

    }

    public function updateKit (Request $request, string $id)
    {
        $kitDestribution = KitDistribution::find($id);

        if (!$kitDestribution) return response()->json(["status" => false, "message" => "No such kit for current beneficiary !"], 404);

        $validated = $request->validate([
            "kitId" => "required|exists:kits,id",
            "destribution_date" => "required|date",
            "remark" => "required|string",
            "is_received" => "required|in:0,1"
        ]);

        $kitDestribution->update($validated);

        return response()->json(["status" => true, "message" => "Kit successfully updated !"]);
        
    }

    public function updateMealtool (Request $request, string $id)
    {

        $mealTool = MealTool::find($id);

        if (!$mealTool)
            return response()->json(["status" => false, "message" => "No such mealtool in system !", "data" => []], 404);


        $mt = $request->all();

        $mealTool->update($mt);

        return response()->json(["status" => true, "message" => "Mealtool successfully updated !", "data" => []], 200);

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

    public function destroyMealtool(string $id) {
    
        $mealTool = MealTool::find($id);

        if (!$mealTool) return response()->json(["status" => false, "message" => "No such mealtool in database !"], 404);

        $mealTool->delete();

        return response()->json(["status" => true, "message" => "Mealtool successfully deleted !"], 200);

    }

    public function destroySession (string $sessionId)
    {
        $session = IndicatorSession::find($sessionId);
    
        if (!$session) return response()->json(["status" => false, "message" => "No such session for current beneficiary was found !"], 404);

        $session->forceDelete();

        return response()->json(["status" => true, "message" => "Session deleted successfully !"], 200);
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

    public function addBeneficiaryToKitList(Request $request) {

        $request->validate([
            "program" => "required|integer|exists:programs,id",
            "indicator" => "required|integer|exists:indicator,id",
            "ids" => "required|array",
            "ids.*" => "integer|exists:beneficiaries,id"
        ]);

        $beneficiaries = Beneficiary::whereIn("id", $request->input("ids"))->get();

        if ($beneficiaries->isEmpty()) return response()->json(["status" => false, "message" => "No such beneficiaries in system !", "data" => []], 404);

        $kitDbId = Database::where("name", "kit_database")->first()->id;

        $indicators = $request->input("indicators");

        if (!$kitDbId) 
                return response()->json(["status" => false, "message" => "Kit database is not a valid database !", "data" => []], 404);

        foreach ($beneficiaries as $beneficiary) {

            $beneficiary->programs()->syncWithoutDetaching($request->input("program"), [
                "database_id" => $kitDbId
            ]);

            $beneficiary->indicators()->sync($indicators);

        }

        return response()->json(["status" => true, "message" => "Beneficiaries successfully added to kit list !", "data" => []], 200);
    }

    public function changeBeneficiaryStatus(string $id) {}

    public function includeOrExcludeBeneficiaryToOrFromAPR(string $newState) {}

}
