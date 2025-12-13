<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewAprRequest;
use App\Models\Apr;
use App\Models\Database;
use App\Models\Project;
use App\Models\Province;
use App\Models\User;
use App\Models\Notification;
use App\Traits\AprToolsTrait;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\AprLog;
use Illuminate\Support\Facades\Auth;


class DatabaseController extends Controller
{
    use AprToolsTrait;

    public function indexSubmittedAndFirstRejectedDatabases ()
    {
        $submittedDatabases = Apr::where("status", "submitted")->orWhere("status", "firstRejected")->orWhere("status", "secondRejected")->get();

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

    public function indexFirstApprovedAndSecondRejectedDatabases ()
    {
        $firstApprovedOrSecondRejectedDatabases = Apr::where("status", "firstApproved")->orWhere("status", "secondRejected")->get();

        if ($firstApprovedOrSecondRejectedDatabases->isEmpty()) return response()->json(["status" => false, "message" => 'No database was found in the "First Approved" or "second rejected" stages!', 'data' => []], 404);

        $firstApprovedOrSecondRejectedDatabases->map(function ($database) {

            $database["projectCode"] = Project::find($database["project_id"])->projectCode;
            $database["province"] = Province::find($database["province_id"])->name;
            $database["database"] = Database::find($database["database_id"])->name;

            unset($database["project_id"], $database["database_id"], $database["province_id"]);

            return $database;

        });

        return response()->json(["status" => true, "message" => "", "data" => $firstApprovedOrSecondRejectedDatabases]);
    }

    public function indexFirstApprovedDatabases ()
    {
        $approvedDatabases = Apr::where("status", "firstApproved")->orWhere("status", "thirdRejected")->get();

        if ($approvedDatabases->isEmpty()) return response()->json(["status" => false, "message" => 'No database was found in the "First Approved" stage!', "data" => []], 404);

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

        if (!$submittedDatabase) return response()->json(["status" => false, "message" => "No such database in system !"], 404);

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

    public function changeDatabaseStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            "newStatus" => "required|in:firstApproved,firstRejected,secondApproved,secondRejected",
        ]);

        $apr = Apr::find($id);

        if (!$apr) {
            return response()->json([
                "status" => false,
                "message" => "No such APR found in the system.",
            ], 404);
        }

        $approver = User::find(Auth::id());
        $approverName = $approver ? $approver->name : 'Unknown user';

        $responsibleUsersForCommingStep = User::permission('Database_submission.view')->get();

        if ($responsibleUsersForCommingStep->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "The system cannot update the APR status because there is no available user with the role HoD, DHoD, or FM to forward the APR.",
            ]);
        }

        $apr->status = $validated["newStatus"];
        $apr->save();

        AprLog::create([
            "apr_id" => $apr->id,
            "user_id" => Auth::id(),
            "action" => $validated["newStatus"],
            "comment" => $validated["comment"] ?? null
        ]);

        $isApproved = $validated["newStatus"] === "firstApproved";

        $notificationTitle = $isApproved
            ? "Database Approved"
            : "Database Rejected";

        $notificationMessage = $isApproved
            ? "A new database has been approved. Please review it."
            : "The database you submitted has been rejected by {$approverName}.";

        $notification = Notification::create([
            "title"   => $notificationTitle,
            "message" => $notificationMessage,
            "type"    => "submittedDatabase",
            "apr_id"  => $apr->id,
        ]);

        if ($validated["newStatus"] == "firstApproved")
            foreach ($responsibleUsersForCommingStep as $user) {
                $user->notifications()->attach($notification->id, ['readAt' => false]);
                event(new MessageSent($user->id, $notificationMessage));
            }
        else {

            $correspondingAprLogInSubmitStage = AprLog::where("action", "submitted")->where("apr_id", $apr->id)->first();

            if (!$correspondingAprLogInSubmitStage)
                return response()->json(["status" => false, "message" => "Warning: The system could not find selected apr log in submitted stage so it can not notify anyone to check it !"]);

            $submitter = User::find($correspondingAprLogInSubmitStage->user_id);

            if (!$submitter)
                return response()->json(["status" => false, "message" => "Warning: The system could not find selected apr submitter, so it can not notify anyone to check it !"]);
            $submitter->notifications()->attach($notification->id, ['readAt' => false]);
            event(new MessageSent($submitter->id, $notificationMessage));

        }

        $messageHelperWord = $isApproved ? "Approved" : "Rejected";

        return response()->json([
            "status"  => true,
            "message" => "APR status changed to {$messageHelperWord}.",
        ], 200);
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

        $createdDatabase = Apr::create($validated);

        $selectedManagerId = $validated["manager_id"];

        $manager = User::find($selectedManagerId);

        $notification = Notification::create([
            "title" => "New database submitted !",
            "message" => "A new database has been submitted check it now !",
            "type" => "submittedDatabase",
            "apr_id" => $createdDatabase->id
        ]);

        $manager->notifications()->attach($notification->id, ['readAt' => false]);

        event(new MessageSent($selectedManagerId, 
        
        "New database submitted !"
    
        ));

        AprLog::create([
            "apr_id" => $createdDatabase->id,
            "user_id" => Auth::id(),
            "action" => "submitted",
            "comment" => $validated["comment"] ?? null
        ]);

        return response()->json(["status" => true, "message" => "New database successfully submitted !"], 200);
    }
}
