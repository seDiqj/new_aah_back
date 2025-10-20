<?php

namespace App\Http\Controllers;

use App\Models\Beneficiary;
use App\Models\BeneficiaryCommunityDialogueSession;
use App\Models\CommunityDialogue;
use App\Models\CommunityDialogueSession;
use App\Models\Database;
use App\Models\DatabaseProgramBeneficiary;
use App\Models\District;
use App\Models\Group;
use App\Models\Indicator;
use App\Models\Program;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityDialogueDatabaseController extends Controller
{
    public function indexBeneficiaries ()
    {

        $communityDialogueDb = Database::where("name", "cd_database")->first();

        if (!$communityDialogueDb) return response()->json(["status" => false, "message" => "Community dialogue is not a valid database !"], 404);

        $communityDialogueDbId = $communityDialogueDb->id;

        $beneficiariesIds = DatabaseProgramBeneficiary::where("database_id", $communityDialogueDbId)->pluck("beneficiary_id")->toArray();

        $beneficiaries = Beneficiary::whereIn("id", $beneficiariesIds)->get();

        if ($beneficiaries->isEmpty()) return response()->json(["status" => false, "message" => "No beneficiary fount for community dialogue database !"]. 404);

        return response()->json(["status" => true, "message" => "" , "data" => $beneficiaries]);
    }

    public function indexCommunityDialogues ()
    {
        $communityDialogues = CommunityDialogue::all();

        if ($communityDialogues->isEmpty()) return response()->json(["status" => false, "message" => "No community dialogue was found !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $communityDialogues]);
    }

    public function indexCommunityDialoguesForSelection()
    {
        $communityDialogues = CommunityDialogue::with("program", "groups")
            ->get(["id", "program_id"]);

        if ($communityDialogues->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No community dialogue available !"
            ], 404);
        }

        $communityDialogues->map(function ($communityDialogue) {

            $communityDialogue->program->database = Database::find($communityDialogue->program->database_id)->name;

            $communityDialogue->program->projectCode = Project::find($communityDialogue->program->project_id)->projectCode;

            $communityDialogue->program->district = District::find($communityDialogue->program->district_id)->name;

            $communityDialogue->program->province = Province::find($communityDialogue->program->province_id)->name;

            unset($communityDialogue["created_at"], $communityDialogue["updated_at"]);
            unset($communityDialogue->program["created_at"], $communityDialogue->program["updated_at"], $communityDialogue->program["project_id"], $communityDialogue->program["database_id"], $communityDialogue->program["province_id"], $communityDialogue->program["district_id"]);

            return $communityDialogue;
        });

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $communityDialogues
        ]);
    }

    public function indexBeneficirySessions (string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status", false, "message" => "No such beneficiary in system !"], 404);

        $sessions = $beneficiary->cdSessions;

        if ($sessions->isEmpty()) return response()->json(["status" => false, "message" => "No session was found for current beneficiary !"], 404);

        $sessions->map(function ($session) {
            unset($session["community_dialogue_id"]);

            return $session;
        });

        return response()->json(["status" => true, "message" => "", "data" => $sessions]);
    }

    public function indexCdSessions (string $id)
    {
        $communityDialogue = CommunityDialogue::find($id);

        if (!$communityDialogue) return response()->json(["status" => false, "message" => "No such community dialogue in system !"], 404);

        $sessions = $communityDialogue->sessions;

        if ($sessions->isEmpty()) return response()->json(["status" => false, "message" => "No sessions was found for current community dialogue !"], 404);

        $sessions->map(function ($session) {
            unset($session["community_dialogue_id"]);

            return $session;
        });

        return response()->json(["status" => true, "message" => "" , "data" => $sessions]);
    }

    public function indexCommunityDialogueGroupBeneficiaries (string $id)
    {
        $group = Group::find($id);

        if (!$group) return response()->json(["status" => false, "message" => "No such group in system !"], 404);

        $beneficiaries = $group->beneficiaries;

        if ($beneficiaries->isEmpty()) return response()->json(["status" => false, "message" => "No beneficiaries was found for current group !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries]);
    }

    public function showBeneficiary (string $id)
    {
        $beneficiary = Beneficiary::select("id", "name", "fatherHusbandName", "gender", "age", "incentiveReceived", "incentiveAmount", "jobTitle", "nationalId", "phone", "maritalStatus")->find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such Beneficiary in system !"], 404);

        $beneficiary->incentiveReceived = (bool) $beneficiary->incentiveReceived;

        return response()->json(["status" => true, "message" => "", "data" => $beneficiary]);
    }

    public function showCD (string $id)
    {
        $communityDialogue = CommunityDialogue::with(["program", "groups"])->find($id);

        if (!$communityDialogue) return response()->json(["status" => false, "message" => "No such community dialogue in system !"], 404);

        $communityDialogue["program"]["projectCode"] = Project::find($communityDialogue["program"]["project_id"])->projectCode;

        $communityDialogue["program"]["database"] = Database::find($communityDialogue["program"]["database_id"])->name;

        $communityDialogue["district"] = District::find($communityDialogue["program"]["district_id"])->name;

        $communityDialogue["program"]["province"] = Province::find($communityDialogue["program"]["province_id"])->name;

        unset(
            $communityDialogue["program_id"], 
            $communityDialogue["project_id"], 
            $communityDialogue["database_id"], 
            $communityDialogue["province_id"], 
            $communityDialogue["district_id"]
        );

        $communityDialogue["groups"]->map(function ($group) {
            unset($group["community_dialogue_id"]);
        });

        return response()->json(["status" => true, "message" => "", "data" => $communityDialogue]);

    }

    public function storeBeneficiary (Request $request)
    {
        $communityDialogueDb = Database::where("name", "cd_database")->first();

        if (!$communityDialogueDb) return response()->json(["status" => false, "message" => "Community dialogue is not a valid database !"], 404);

        $communityDialogueDbId = $communityDialogueDb->id;

        $data = $request->all();

        $beneficiary = Beneficiary::create($data);

        DatabaseProgramBeneficiary::create([
            "database_id" => $communityDialogueDbId,
            "beneficiary_id" => $beneficiary->id
        ]);

        return response()->json(["status" => true, "message" => "Beneficiary successfully created !"], 200);
        
    }

    public function storeCommunityDialogue (Request $request)
    {

        $communityDialogueDatabase = Database::where("name", "cd_database")->first();

        if (!$communityDialogueDatabase) return response()->json(["status" => false, "message" => "Community dialogue is not a valid database !"], 422);


        $programInformation = $request->input("programInformation");

        $communityDialogueIndicator = Indicator::find($programInformation["indicator_id"]);


        if (!$communityDialogueDatabase) return response()->json(["status" => false, "message" => "Invalid indicator selected !"], 422);

        // temprory
        $programInformation["siteCode"] = "200";
        $programInformation["healthFacilityName"] = "200";
        $programInformation["interventionModality"] = "200";
        // temprory
        $programInformation["database_id"] = $communityDialogueDatabase->id;

        $program = Program::create($programInformation);

        $communityDialogue = CommunityDialogue::create([
            "program_id" => $program->id,
            "indicator_id" => $communityDialogueIndicator->id,
            "remark" => $request->remark
        ]);

        $sessions = $request->input("sessions");

        foreach($sessions as $session) {
            $communityDialogue->sessions()->create($session);
        }

        $groups = $request->input("groups");

        foreach($groups as $group) {
            $communityDialogue->groups()->create([
                "name" => $group
            ]);
        }

        return response()->json(["status" => true, "message" => "Community dialogue successfully created !"], 200);

    }

    public function updateBeneficiary (Request $request, string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);


        $validated = $request->all();

        $beneficiary->update($validated);

        return response()->json(["status" => true, "message" => "Beneficiary successfully updated !"], 200);

    }

    public function destroyBeneficiaries (Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        Beneficiary::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Beneficiaries successfully deleted !"], 200);
    }

    public function destroyCommunityDialogue (Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        CommunityDialogue::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Community dialogues successfully deleted !"], 200);
    }

    public function destroyBeneficiarySessions (Request $request, string $id)
    {

        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $ids = $request->input("ids");

        $beneficiary->cdSessions()->whereIn('community_dialogue_session_id', $ids)->delete();

        return response()->json(["status" => true, "message" => "Sessions successfully removed !"], 200);

    }

    public function addCommunityDialogueToBeneficiaries(Request $request)
    {
        $beneficiaryIds = $request->input("ids");
        $communityDialogueInput = $request->input("communityDialogue");

        if (empty($beneficiaryIds) || empty($communityDialogueInput) || !isset($communityDialogueInput[0])) {
            return response()->json([
                "status" => false,
                "message" => "Invalid input!"
            ], 400);
        }

        $groupId = $communityDialogueInput[0]["group"]["id"];
        $communityDialogueId = $communityDialogueInput[0]["communityDialogueId"];

        $beneficiaries = Beneficiary::whereIn("id", $beneficiaryIds)->get();

        if ($beneficiaries->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No such beneficiary / beneficiaries in system!"
            ], 404);
        }

        $communityDialogue = CommunityDialogue::find($communityDialogueId);

        if (!$communityDialogue) {
            return response()->json([
                "status" => false,
                "message" => "No such community dialogue in system!"
            ]);
        }

        $communityDialogueSessionIds = CommunityDialogueSession::where("community_dialogue_id", $communityDialogueId)
                            ->pluck("id")
                            ->toArray();

        DB::transaction(function () use ($beneficiaries, $communityDialogue, $communityDialogueSessionIds, $groupId) {
            foreach ($beneficiaries as $beneficiary) {
                foreach ($communityDialogueSessionIds as $sessionId) {
                    BeneficiaryCommunityDialogueSession::firstOrCreate(
                        [
                            "beneficiary_id" => $beneficiary->id,
                            "community_dialogue_session_id" => $sessionId
                        ],
                        ["isPresent" => true]
                    );
                }

                $beneficiary->groups()->syncWithoutDetaching([
                    $groupId => ['updated_at' => now()]
                ]);

                $beneficiary->indicators()->syncWithoutDetaching([
                    $communityDialogue->indicator_id => ["updated_at" => now()]
                ]);

                DatabaseProgramBeneficiary::create([
                    "database_id" => Database::where("name", "cd_database")->first()->id,
                    "program_id" => $communityDialogue->program_id,
                    "beneficiary_id" => $beneficiary->id
                ]);
            }
        });

        return response()->json([
            "status" => true,
            "message" => "Beneficiaries successfully added to the community dialogue!"
        ]);
    }

}
