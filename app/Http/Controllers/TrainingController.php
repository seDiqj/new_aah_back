<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddTrainingToBeneficiaryRequest;
use App\Http\Requests\StoreChapterRequest;
use App\Http\Requests\StorePreAndPostTestRequest;
use App\Http\Requests\StoreTrainingBeneficiaryRequest;
use App\Http\Requests\StoreTrainingRequest;
use App\Models\Beneficiary;
use App\Models\Chapter;
use App\Models\Database;
use App\Models\DatabaseProgramBeneficiary;
use App\Models\District;
use App\Models\Indicator;
use App\Models\Project;
use App\Models\Province;
use App\Models\Training;
use App\Models\TrainingEvaluation;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    public function index () {

        $trainings = Training::all();

        if ($trainings->isEmpty()) return response()->json(["status" => false, "message" => "No training found !"], 404);

        $trainings = $trainings->map(function ($training) {
            $training["projectCode"] = Project::find($training->project_id)->projectCode;
            $training["province"] = Province::find($training->province_id)->name;
            $training["indicator"] = Indicator::find($training->indicator_id)->indicator;
            $training["district"] = District::find($training->district_id)->name;

            unset($training["project_id"]);
            unset($training["province_id"]);
            unset($training["indicator_id"]);
            unset($training["district_id"]);

            return $training;
        });

        return response()->json(["status" => true, "message" => "", "data" => $trainings]);

    }

    public function indexBeneficiaries ()
    {
        $trainingDB = Database::where("name", "training_database")->first();

        if (!$trainingDB) return response()->json(["status" => false, "message" => "Training database is not a valid database"], 404);

        $beneficiaries = $trainingDB->beneficiaries;

        if ($beneficiaries->isEmpty()) return response()->json(["status" => false, "message" => "No beneficiary was found for training database !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries]);
    }

    public function indexBeneficiaryTrainings(string $id)
    {
        $beneficiary = Beneficiary::with("trainings.chapters")
            ->select(
                "id",
                "name",
                "fatherHusbandName",
                "gender",
                "age",
                "phone",
                "email",
                "participantOrganization",
                "jobTitle"
            )
            ->find($id);

        if (! $beneficiary) {
            return response()->json([
                "status" => false,
                "message" => "No such beneficiary in system !"
            ], 404);
        }

        $beneficiaryChapters = $beneficiary->chapters()
            ->select("chapters.id")
            ->get()
            ->map(function ($chapter) {
                return [
                    "id" => $chapter->pivot->chapter_id,
                    "isPresent" => (bool) $chapter->pivot->isPresent,
                    "preTestScore" => $chapter->pivot->preTestScore,
                    "postTestScore" => $chapter->pivot->postTestScore,
                ];
            });

        $beneficiary->trainings->map(function ($training) {
            $training->aprIncluded = (bool) $training->aprIncluded;

            $training->district = Province::find($training->province_id)?->name ?? null;
            $training->projectCode = Project::find($training->project_id)?->projectCode ?? null;
            $training->indicator = Indicator::find($training->indicator_id)?->indicator ?? null;

            unset(
                $training->project_id,
                $training->district_id,
                $training->province_id,
                $training->indicator_id,
                $training->created_at,
                $training->updated_at,
                $training->pivot
            );

            $training->chapters->map(function ($chapter) {
                unset($chapter->training_id, $chapter->created_at, $chapter->updated_at);
                return $chapter;
            });

            return $training;
        });

        $beneficiary->selfChapters = $beneficiaryChapters;

        unset($beneficiary->id);

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $beneficiary
        ]);
    }

    public function indexTrainingsForSelection ()
    {
        $trainings = Training::select("name")->get();

        if ($trainings->isEmpty()) return response()->json(["status" => false, "message" => "No such training in system !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $trainings]);
    }

    public function store (StoreTrainingRequest $request)
    {

        $project = Project::where("projectCode", $request->input("projectCode"))->first();
        
        if (!$project) return response()->json(["status" => false, "message" => "Invalid project code !"], 404);

        $province = Province::where("name", $request->input("province"))->first();

        if (!$province) return response()->json(["status" => false, "message" => "Invalid province !"], 404);

        $indicator = Indicator::where("indicator", $request->input("indicator"))->first();

        if (!$indicator) return response()->json(["status" => false, "message" => "Invalid indicator !"], 404);

        $district = District::where("name", $request->input("district"))->first();

        if (!$district) return response()->json(["status" => false, "message" => "Invalid district !"], 404);

        $validated = $request->except("chapters");

        $validated["project_id"] = $project->id;

        $validated["province_id"] = $province->id;

        $validated["indicator_id"] = $indicator->id;

        $validated["district_id"] = $district->id;

        unset($validated["projectCode"]);
        unset($validated["province"]);
        unset($validated["indicator"]);
        unset($validated["district"]);


        $training = Training::create($validated);

        $chapters = $request->input("chapters");

        foreach ($chapters as $chapter) {

            $training->chapters()->create($chapter);

        }

        return response()->json(["status" => true, "message" => "Training successfully created !"], 200);
    }

    public function storeNewChapter (StoreChapterRequest $request, string $id)
    {
        $validated = $request->validated();

        $training = Training::find($id);

        if (!$training) return response()->json(["status" => false, "message" => "No such training in system !"], 404);

        $training->chapters()->create($validated);

        return response()->json(["status" => true, "message" => "Chapter successfully added !"], 200);
    }

    public function storeNewBeneficiary (StoreTrainingBeneficiaryRequest $request)
    {
        $validated = $request->validated();

        $trainingDb = Database::where("name", "training_database")->first();

        if (!$trainingDb) return response()->json(["status" => false, "message" => "Training database is not in system !"], 404);

        $trainingDbId = $trainingDb->id;

        $validated["aprIncluded"] = true;

        $beneficiary = Beneficiary::create($validated);

        DatabaseProgramBeneficiary::create([
            "database_id" => $trainingDbId,
            "beneficiary_id" => $beneficiary->id,
        ]);

        return response()->json(["status" => true, "message" => "Beneficiary successfully created !"], 200);
    }

    public function update(StoreTrainingRequest $request, string $id)
    {
        $training = Training::find($id);

        if (!$training)
            return response()->json([
                "status" => false,
                "message" => "No such training found!"
            ], 404);

        $project = Project::where("projectCode", $request->input("projectCode"))->first();
        if (!$project)
            return response()->json(["status" => false, "message" => "Invalid project code!"], 404);

        $province = Province::where("name", $request->input("province"))->first();
        if (!$province)
            return response()->json(["status" => false, "message" => "Invalid province!"], 404);

        $indicator = Indicator::where("indicator", $request->input("indicator"))->first();
        if (!$indicator)
            return response()->json(["status" => false, "message" => "Invalid indicator!"], 404);

        $district = District::where("name", $request->input("district"))->first();
        if (!$district)
            return response()->json(["status" => false, "message" => "Invalid district!"], 404);

        $validated = $request->except("chapters");

        $validated["project_id"] = $project->id;
        $validated["province_id"] = $province->id;
        $validated["indicator_id"] = $indicator->id;
        $validated["district_id"] = $district->id;

        unset($validated["projectCode"], $validated["province"], $validated["indicator"], $validated["district"]);

        $training->update($validated);

        $training->chapters()->delete();

        $chapters = $request->input("chapters", []);
        foreach ($chapters as $chapter) {
            $training->chapters()->create($chapter);
        }

        return response()->json([
            "status" => true,
            "message" => "Training successfully updated!"
        ], 200);
    }
    
    public function updateBeneficiary(StoreTrainingBeneficiaryRequest $request, string $id)
    {
        $validated = $request->validated();

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) {
            return response()->json([
                "status" => false,
                "message" => "No such beneficiary found in the system!"
            ], 404);
        }

        $beneficiary->update($validated);

        $trainingDb = Database::where("name", "training_database")->first();
        if ($trainingDb) {
            DatabaseProgramBeneficiary::updateOrCreate(
                [
                    "beneficiary_id" => $beneficiary->id,
                    "database_id" => $trainingDb->id,
                ],
                [] 
            );
        }

        return response()->json([
            "status" => true,
            "message" => "Beneficiary successfully updated!",
            "data" => $beneficiary
        ], 200);
    }

    public function show(string $id)
    {
        $training = Training::with(["chapters", "evaluations"])->find($id);

        if (!$training)
            return response()->json([
                "status" => false,
                "message" => "No such training in system !"
            ], 404);

        $training["projectCode"] = optional(Project::find($training->project_id))->projectCode;
        $training["province"] = optional(Province::find($training->province_id))->name;
        $training["indicator"] = optional(Indicator::find($training->indicator_id))->indicator;
        $training["district"] = optional(District::find($training->district_id))->name;

        $training->makeHidden([
            "project_id",
            "province_id",
            "indicator_id",
            "district_id",
            "created_at",
            "updated_at"
        ]);

        $training->chapters = $training->chapters->map(function ($chapter) {
            unset($chapter->created_at, $chapter->updated_at, $chapter->training_id);
            return $chapter;
        });

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $training
        ]);
    }

    public function showBeneficiary(string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) {
            return response()->json([
                "status" => false,
                "message" => "No such beneficiary found in the system!"
            ], 404);
        }

        $data = [
            "id" => $beneficiary->id,
            "name" => $beneficiary->name,
            "fatherHusbandName" => $beneficiary->fatherHusbandName,
            "gender" => $beneficiary->gender,
            "age" => $beneficiary->age,
            "phone" => $beneficiary->phone,
            "email" => $beneficiary->email,
            "participantOrganization" => $beneficiary->participantOrganization,
            "jobTitle" => $beneficiary->jobTitle,
            "dateOfRegistration" => $beneficiary->dateOfRegistration,
        ];

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $data
        ], 200);
    }

    public function destroy (Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        Training::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Trainings successfully deleted !"], 200);
    } 

    public function destroyBeneficiaries (Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        Beneficiary::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Trainings successfully deleted !"], 200);
    }

    public function addTrainingToBeneficiaries (AddTrainingToBeneficiaryRequest $request)
    {

        $validated = $request->validated();

        $training = Training::where("name", $validated["training"])->first();

        if (!$training) return response()->json(["status" => false, "message" => "No such training in system !"], 404);

        $beneficiariesIds = $validated["ids"];

        $training->beneficiaries()->attach($beneficiariesIds);

        $trainingChaptersIds = $training->chapters()->pluck("id")->toArray();

        foreach ($beneficiariesIds as $beneficiaryId) {
            $beneficiary = Beneficiary::find($beneficiaryId);

            $beneficiary->chapters()->attach($trainingChaptersIds);
        }

        return response()->json(["status" => true, "message" => "Training " . $training->name . " successfully assigned to " . (string) count($beneficiariesIds) . " beneficiaries !"], 200);
    }

    public function togglePresence (string $beneficiaryId, string $chapterId)
    {
        $beneficiary = Beneficiary::find($beneficiaryId);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $chapter = Chapter::find($chapterId);

        if (!$chapter) return response()->json(["status" => false, "message" => "No such chapter in system !"], 404);

        $currentPivot = $beneficiary->chapters()
                                    ->where("chapters.id", $chapterId)
                                    ->first()
                                    ->pivot;

        $newStatus = !$currentPivot->isPresent;

        $beneficiary->chapters()->updateExistingPivot($chapterId, [
            "isPresent" => $newStatus
        ]);

        return response()->json(["status" => true, "message" => "The precense status of beneficiary changed to " . (string) ($newStatus ? "true" : "false")], 200);

    }

    public function setPreAndPostTest (StorePreAndPostTestRequest $request, string $beneficiaryId, string $chapterId)
    {

        $validated = $request->validated();

        $beneficiary = Beneficiary::find($beneficiaryId);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $chapter = Chapter::find($chapterId);

        if (!$chapter) return response()->json(["status" => false, "message" => "No such chapter in system !"], 404);

        $newPreTestScore = $validated["preTestScore"];
        $newPostTestScore = $validated["postTestScore"];
        $newRemark = $validated["remark"];

        $beneficiary->chapters()->updateExistingPivot($chapterId, [
            "preTestScore" => $newPreTestScore,
            "postTestScore" => $newPostTestScore,
            "remark" => $newRemark
        ]);

        return response()->json(["status" => true, "message" => "Pre Test And Post Test successfully updated"], 200);
    }

    public function showBeneficiaryChapterPreAndPostTestScores (string $beneficiaryId, string $chapterId)
    {
        $beneficiary = Beneficiary::find($beneficiaryId);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $chapter = Chapter::find($chapterId);

        if (!$chapter) return response()->json(["status" => false, "message" => "No such chapter in system !"], 404);

        $chapter = $beneficiary->chapters()
                                ->where("chapters.id", $chapterId)
                                ->first();

        $data = [
            "preTestScore" => $chapter->pivot->preTestScore,
            "postTestScore" => $chapter->pivot->postTestScore,
            "remark" => $chapter->pivot->remark
        ];

        return response()->json(["status" => true, "message" => "", "data" => $data]);

    }

    public function storeTrainingEvaluation (Request $request, string $trainingId)
    {
        $training = Training::find($trainingId);

        if (!$training) return response()->json(["status" => false, "message" => "No such training in system !"], 404);

        $evaluations = $request->input("evaluations");

        $evaluationsRemark = $request->input("remark");

        TrainingEvaluation::updateOrCreate(
            ["training_id" => $trainingId],
            ["evaluations" => $evaluations, "remark" => $evaluationsRemark]
        );

        return response()->json(["status" => true, "message" => "Evaluation successfully added !"]);
    }

    public function showTrainingEvaluation (string $trainingId)
    {
        $training = Training::find($trainingId);

        if (!$training) return response()->json(["status" => false, "message" => "No such training in system !"], 404);

        
    }
}
