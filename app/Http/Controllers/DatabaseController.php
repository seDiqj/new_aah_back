<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewAprRequest;
use App\Models\Apr;
use App\Models\Database;
use App\Models\Project;
use App\Models\Province;
use App\Traits\AprToolsTrait;
use Illuminate\Http\Request;

class DatabaseController extends Controller
{
    use AprToolsTrait;

    public function indexSubmittedDatabases ()
    {
        $submittedDatabases = Apr::where("status", "submitted")->get();

        if (!$submittedDatabases) return response()->json(["status" => false, "message" => "No submitted databases found !"], 404);


        $submittedDatabases->map(function ($submittedDatabase) {

            $submittedDatabase["projectCode"] = Project::find($submittedDatabase["project_id"])->projectCode;
            $submittedDatabase["province"] = Province::find($submittedDatabase["province_id"])->name;
            $submittedDatabase["database"] = Database::find($submittedDatabase["database_id"])->name;

            unset($submittedDatabase["project_id"], $submittedDatabase["database_id"], $submittedDatabase["province_id"]);

            return $submittedDatabase;

        });

        return response()->json(["status" => true, "message" => "", "data" => $submittedDatabases]);
    }

    public function indexFirstApprovedDatabases ()
    {
        $approvedDatabases = Apr::where("status", "firstApproved")->get();

        if ($approvedDatabases->isEmpty()) return response()->json(["status" => false, "message" => "No submitted databases found !"], 404);

        $approvedDatabases->map(function ($approvedDatabase) {

            $approvedDatabase["projectCode"] = Project::find($approvedDatabase["project_id"])->projectCode;
            $approvedDatabase["province"] = Province::find($approvedDatabase["province_id"])->name;
            $approvedDatabase["database"] = Database::find($approvedDatabase["database_id"])->name;

            unset($approvedDatabase["project_id"], $approvedDatabase["database_id"], $approvedDatabase["province_id"]);

            return $approvedDatabase;

        });

        return response()->json(["status" => true, "message" => "", "data" => $approvedDatabases]);
    }

    public function showSubmittedDatabase (string $id)
    {
        $submittedDatabase = Apr::find($id);

        $project = Project::find($submittedDatabase->project_id);

        if (!$project) return response()->json(["status" => false, "message" => "Invalid project for selected database !"]);

        $database = Database::find($submittedDatabase->database_id);

        if (!$database) return response()->json(["status" => false, "message" => "Invalid database for selected database !"]);

        $province = Province::find($submittedDatabase->province_id);

        if (!$province) return response()->json(["status" => false, "message" => "Invalid province for selected database !"]);

        $numberOfProjectIndicators = $this->projectIndicatorsToASpicificDatabase($project, $database->id)->count();

        $numberOfProjectOutputs = $this->projectOutputsToASpicificDatabase($project, $database->id)->count();

        $numberOfProjectOutcomes = $this->projectOutcomesToASpicificDatabase($project, $database->id)->count();

        $submittedDatabaseBeneficiaries = $this->projectBeneficiariesToASpicificDatabaseAndSpicificProvince($project, $database->id, $province->id);

        $finalData = [
            "project" => [
                "id" => $project->id,
                "projectCode" => $project->projectCode,
            ],
            "database" => $database->name,
            "province" => $province->name,
            "numOfIndicators" => $numberOfProjectIndicators,
            "numOfOutputs" => $numberOfProjectOutputs,
            "numOfOutcomes" => $numberOfProjectOutcomes,
            "beneficiaries" => $submittedDatabaseBeneficiaries,
            "fromDate" => $submittedDatabase->fromDate,
            "toDate" => $submittedDatabase->toDate,
        ];

        return response()->json(["status" => true, "message" => "", "data" => $finalData]);
    }

    public function destroySubmittedDatabases (Request $request)
    {
        $validated = $request->validate([
            "ids" => "required|array",
            "ids.*" => "required|integer",
        ]);

        $ids = $validated["ids"];

        Apr::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Selected databases successfully removed from submitted list !"], 200);
    }

    public function destroyFirstApprovedDatabases (Request $request)
    {
        $validated = $request->validate([
            "ids" => "required|array",
            "ids.*" => "required|integer",
        ]);

        $ids = $validated["ids"];

        Apr::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Selected databases successfully removed from approved list !"], 200);
    }

    public function changeDatabaseStatus (Request $request, string $id)
    {
        $apr = Apr::find($id);

        if (!$apr) return response()->json(["status" => false, "message" => "No such submitted apr in system !"], 404);

        $validated = $request->validate(
            [
                "newStatus" => "in:submitted,firstApproved,firstRejecte,reviewed,secondApproved,secondRejected"
            ]
        );

        $apr->status = $validated["newStatus"];
        $apr->save();

        $messageHelperWord = $validated["newStatus"] == "firstApproved" ? "Approved" : "Rejected";

        return response()->json(["status" => true, "message" => "Apr status changed to $messageHelperWord"], 200);
    }

    public function submitNewDatabase (StoreNewAprRequest $request)
    {
        $validated = $request->validated();

        $fromDatePartsList = explode("-", $validated["fromDate"]);

        $fromMonthToNumber = date("m", strtotime($fromDatePartsList[1]));

        $validated["fromDate"] = \Carbon\Carbon::createFromFormat("d-m-Y", "01" . "-" . $fromMonthToNumber . "-" . $fromDatePartsList[0])->format("Y-m-d");

        $toDatePartsList = explode("-", $validated["toDate"]);

        $toMonthToNumber = date("m", strtotime($toDatePartsList[1]));

        $validated["toDate"] = \Carbon\Carbon::createFromFormat("d-m-Y", "01" . "-" . $toMonthToNumber . "-" . $toDatePartsList[0])->format("Y-m-d");;

        $validated["status"] = "submitted";

        $exist = Apr::where("project_id", $validated["project_id"])->where("database_id", $validated["database_id"])->where("province_id", $validated["province_id"])->where("fromDate", $validated["fromDate"])->where("toDate", $validated["toDate"])->first();

        if ($exist) return response()->json(["status" => true, "message" => "Selected database has been previosly submitted !"], 200);

        Apr::create($validated);

        return response()->json(["status" => true, "message" => "New database successfully submitted !"], 200);
    }
}
