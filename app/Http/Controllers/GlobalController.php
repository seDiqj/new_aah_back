<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Indicator;
use App\Models\Program;
use App\Models\Project;
use App\Models\Province;
use App\Models\User;
use App\Models\Apr;
use App\Models\Database;
use App\Models\Beneficiary;
use App\Traits\AprToolsTrait;
use Illuminate\Http\Request;

class GlobalController extends Controller
{

    use AprToolsTrait;

    public function indexManagers ()
    {
        $usersWithRoleManager = User::role("manager")->select("id", "name")->get();

        if ($usersWithRoleManager->isEmpty()) 
            return response()->json(["status" => false, "message" => "No user with role manager was found !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $usersWithRoleManager]);
    }

    public function indexDatabaseIndicators(string $databaseName) {

        $indicators = Indicator::whereHas("database", function ($query) use ($databaseName) {
            $query->where("name", $databaseName);
        })->select("id", "indicatorRef")->get();

        if ($indicators->isEmpty()) return response()->json(["status" => false, "message" => "No indicator was found for " . $databaseName], 404);

        return response()->json(["status" => true, "message" => "", "data" => $indicators], 200);

    }

    public function indexProgramsForSelection(string $databaseName)
    {
        $programs = Program::whereHas("database", function ($query) use ($databaseName) {
            $query->where("name", $databaseName);
        })->select("focalPoint")->get();

        if ($programs->isEmpty()) return response()->json(["status" => false, "message" => "No program for " . $databaseName . " found in system"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $programs]);
    }

    // Just for selection
    public function indexDistricts () {

        $districts = District::select("id", "name")->get();

        return response()->json(["status" => true, "message" => "", "data" => $districts]);
    }

    // Just for selection.
    public function indexProvinces ()
    {
        $provinces = Province::select("name")->get();

        return response()->json(["status" => true, "message" => "", "data" => $provinces]);
    }

    // Just for selection.
    public function indexProjects ()
    {
        $projects = Project::select("id", "projectCode")->get();

        return response()->json(["status" => true, "message" => "", "data" => $projects]);
    }

    public function indexProjectProvinces (string $id)
    {
        $project = Project::find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in system !"], 404);

        $provinces = $project->provinces;

        return response()->json(["status" => true, "message" => "", "data" => $provinces], 200);
    }

    public function indexDatabaseBeneficiaries(string $id) {

        $database = Apr::find($id);

        if (!$database) return response()->json(["status" => false, "message" => "No such database in system !"], 404);

        $beneficiaries = Beneficiary::whereHas("programs", function ($q) use ($database) {
            $q->where("project_id", $database->project_id)->where("province_id", $database->province_id);
        })->whereHas("databases", function ($q) use ($database) {
            $q->where("name", Database::find($database->database_id)->name);
        })->get();

        if ($beneficiaries->isEmpty()) return response()->json(["status" => false, "message" => "No beneficiaries was found for current database !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $beneficiaries], 200);


    }

    public function indexProjectIndicatorsAccordingToAprogram(string $programId, string $databaseName) {

        $program = Program::find($programId);

        if (!$program) return response()->json(["status" => false, "message" => "No such program in system !", "data" => []], 404);

        $programProject = Project::find($program->project_id);

        if (!$programProject) return response()->json(["status" => false, "message" => "The selected program has no valid project in system !", "data" => []], 404);

        $databaseId = Database::where("name", $databaseName)->pluck("id");
        
        if (!$databaseId) return response()->json(["status" => false, "message" => "$databaseName is not a valid database !", "data" => []], 422);

        $indicators = $this->projectIndicatorsToASpicificDatabase($programProject, $databaseId[0]);

        if ($indicators->isEmpty()) return response()->json(["status" => false, "message" => "No indicator was found for selected program, project !", "data" => []], 404);

        return response()->json(["status" => true, "message" => "", "data" => $indicators], 200);

    }

    public function changeBeneficiaryAprIncluded (string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $beneficiary->aprIncluded = !$beneficiary->aprIncluded;

        $beneficiary->save();

        return response()->json(["status" => true, "message" => "Beneficiary apr included changed !"]);
    }


    public function generateWordsList () {


        $wordList = [];

        $lines = file(__DIR__ . '/wordlist.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $wordList[] = $line;
        }

        return response()->json(["status" => true, "message" => "", "data" => $wordList]);


    }
}