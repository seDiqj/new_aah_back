<?php

namespace App\Http\Controllers;
use App\Models\Apr;
use App\Models\Project;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Events\MessageSent;
use App\Models\Database;
use App\Models\Province;
use App\Models\AprLog;
use Illuminate\Support\Facades\Auth;



class AprController extends Controller
{

    public function indexReviewedAprs ()
    {
        $aprs = Apr::where("status", "reviewed")->orWhere("status", "secondApproved")->get();

        if ($aprs->isEmpty()) return response()->json(["status" => false, "message" => "No apr with status reviewed was found !"], 404);

        $aprs->map(function ($reviewedApr) {

            $reviewedApr["projectCode"] = Project::find($reviewedApr["project_id"])->projectCode;
            $reviewedApr["province"] = Province::find($reviewedApr["province_id"])->name;
            $reviewedApr["database"] = Database::find($reviewedApr["database_id"])->name;

            unset($reviewedApr["project_id"], $reviewedApr["database_id"], $reviewedApr["province_id"]);

            return $reviewedApr;

        });

        return response()->json(["status" => true, "message" => "", "data" => $aprs]);
    }

    public function indexGeneratedAprs () {
        $aprs = Apr::where("status", "fourthRejected")->orWhere("status", "aprGenerated")->get();

        if ($aprs->isEmpty()) return response()->json(["status" => false, "message" => "No apr with status Apr Generated was found !"], 404);

        $aprs->map(function ($reviewedApr) {

            $reviewedApr["projectCode"] = Project::find($reviewedApr["project_id"])->projectCode;
            $reviewedApr["province"] = Province::find($reviewedApr["province_id"])->name;
            $reviewedApr["database"] = Database::find($reviewedApr["database_id"])->name;

            unset($reviewedApr["project_id"], $reviewedApr["database_id"], $reviewedApr["province_id"]);

            return $reviewedApr;

        });

        return response()->json(["status" => true, "message" => "", "data" => $aprs]);
    }
    
    public function generateApr (string $id)
    {

        $apr = Apr::find($id);

        if (!$apr || $apr->status != "firstApproved") return response()->json(["status" => false, "message" => "No such database to generat apr !"], 404);

        $instance = new AprGeneratorController();

        $apr->status = 'aprGenerated';
        $apr->save();

        return $instance->generate(
            $apr->project_id,
            $apr->database_id,
            $apr->province_id,
            $apr->fromDate,
            $apr->toDate
        );

    }

    public function showGeneratedApr (string $id) {

        $apr = Apr::find($id);

        if (!$apr || ($apr->status != "aprGenerated" && $apr->status != "secondApproved" && $apr->status != "fourthRejected")) return response()->json(["status" => false, "message" => "No such database for reviewing !"], 404);

        $instance = new AprGeneratorController();

        return $instance->showSpicificDatabaseApr($apr->project_id, $apr->database_id, $apr->province_id, $apr->fromDate, $apr->toDate);

    }

    public function getSystemAprsStatus ()
    {

        $aprs = Apr::all();

        $numberOfAprsWithSpicificStatus = $aprs->map(function ($item) {
            return $item->status;
        })->countBy();
        
        $projectsCount = Project::all()->count();


        return response()->json(["status" => true, "data" => $numberOfAprsWithSpicificStatus], 200); 

    }

    public function markAprAsReviewed(string $id)
    {
        $apr = Apr::find($id);

        if (!$apr) {
            return response()->json([
                "status" => false,
                "message" => "No APR found in the system.",
            ], 404);
        }

        $responsibleUsersForCommingSteps = User::permission('Apr.validate')->get();

        if ($responsibleUsersForCommingSteps->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "The system cannot proceed because no authorized user is available for the next step.",
            ]);
        }

        $apr->status = 'reviewed';
        $apr->save();

        AprLog::create([
            "apr_id" => $apr->id,
            "user_id" => Auth::id(),
            "action" => "reviewed",
            "comment" => null
        ]);

        $notificationTitle = "APR Reviewed";
        $notificationMessage = "An APR has been reviewed and is awaiting further action.";

        $notification = Notification::create([
            "title"   => $notificationTitle,
            "message" => $notificationMessage,
            "type"    => "submittedDatabase",
            "apr_id"  => $apr->id,
        ]);

        foreach ($responsibleUsersForCommingSteps as $user) {
            $user->notifications()->attach($notification->id, ['readAt' => false]);
            event(new MessageSent($user->id, $notificationMessage));
        }

        return response()->json([
            "status" => true,
            "message" => "APR status successfully changed to Reviewed.",
        ], 200);
    }

    public function rejectAprInReviewStage (string $id)
    {
        $apr = Apr::find($id);

        if (!$apr && ($apr->status != "aprGenerated" && $apr->status != "fourthRejected")) return response()->json(["status" => false, 'No APR with status "Apr Generated" found in the system!'], 404);

        $apr->status = "thirdRejected";
        $apr->save();

        $correspondingAprLogInAprGeneratedStage = AprLog::where("action", "secondApproved")->where("apr_id", $id)->first();

        if (!$correspondingAprLogInAprGeneratedStage)
            return response()->json(["status" => false, "message" => "The system could not find the selected APR log in the first approved stage, so it cannot notify anyone to check it!"]);

        $firstApprover = User::find($correspondingAprLogInAprGeneratedStage->user_id);

        if (!$firstApprover)
            return response()->json(["status" => false, "message" => "The system could not find the person whe has approved the apr in first stage so it can not notify anyone to check it !"]);

        $notification = Notification::create([
            "title"   => "Apr rejected (Reviewed stage)",
            "message" => "The apr that you approved has been rejected by " . Auth::user()->name . " check it now !",
            "type"    => "submittedDatabase",
            "apr_id"  => $apr->id,
        ]);

        $firstApprover->notifications()->attach($notification->id, ["readAt" => false]);
        event(new MessageSent($firstApprover->id, "The apr that you approved has been rejected by " . Auth::user()->name . " check it now !"));
    }

    public function approveApr(Request $request, string $id)
    {
        $apr = Apr::find($id);

        if (!$apr) {
            return response()->json([
                "status" => false,
                "message" => "No APR found in the system.",
            ], 404);
        }

        $validated = $request->validate([
            "newStatus" => "required|in:secondApproved,fourthRejected",
        ]);

        $validator = User::find(Auth::id());
        $validatorName = $validator ? $validator->name : 'Unknown user';

        // Its because we wanna to notify all users who has Apr.validate permission that a new apr has been compleatly approved (second stage)
        $usersWhoHasTheSamePermissionsAsMe = User::permission('Apr.validate')->get();

        $apr->status = $validated["newStatus"];
        $apr->save();

        AprLog::create([
            "apr_id" => $apr->id,
            "user_id" => Auth::id(),
            "action" => $validated["newStatus"],
            "comment" => null
        ]);

        $status = $validated["newStatus"];
        $notificationTitle = $status === "secondApproved" ? "APR Approved (Second Stage)" : "APR Rejected (Second Stage)";
        $notificationMessage = $status === "secondApproved"
            ? "A new APR has been approved by {$validatorName} in the second stage."
            : "The APR you reviewed has been rejected by {$validatorName} at the second stage.";

        $notification = Notification::create([
            "title"   => $notificationTitle,
            "message" => $notificationMessage,
            "type"    => "submittedDatabase",
            "apr_id"  => $apr->id,
        ]);

        if ($validated["newStatus"] == "secondApproved")
            foreach ($usersWhoHasTheSamePermissionsAsMe as $user) {
                $user->notifications()->attach($notification->id, ['readAt' => false]);
                event(new MessageSent($user->id, $notificationMessage));
            }
        else {

            $correspondingAprLogInReviwedStage = AprLog::where("action", "reviewed")->where("apr_id", $apr->id)->first();

            if (!$correspondingAprLogInReviwedStage)
                return response()->json(["status" => false, "message" => "The system could not find the selected APR log in the reviewed stage, so it cannot notify anyone to check it!"]);

            $reviewer = User::find($correspondingAprLogInReviwedStage->user_id);

            if (!$reviewer)
                return response()->json(["status" => false, "message" => "The system could not find the selected APR reviewer, so it cannot notify anyone to check it !"]);

            $notification = Notification::create([
                "title"   => $notificationTitle,
                "message" => $notificationMessage,
                "type"    => "submittedDatabase",
                "apr_id"  => $apr->id,
            ]);
            
            $reviewer->notifications()->attach($notification->id, ['readAt' => false]);
            event(new MessageSent($reviewer->id, $notificationMessage));

        }
            

        $friendlyStatus = $status === "secondApproved" ? "Second Approved" : "Second Rejected";

        return response()->json([
            "status"  => true,
            "message" => "APR status successfully changed to {$friendlyStatus}.",
        ], 200);
    }

}
