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
use Illuminate\Http\Request;

class GlobalController extends Controller
{

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

    public function indexDistricts (Request $request) {

        $districts = District::select("id", "name")->get();

        return response()->json(["status" => true, "message" => "", "data" => $districts]);
    }

    public function indexProvinces (Request $request)
    {
        $provinces = Province::select("name")->get();

        return response()->json(["status" => true, "message" => "", "data" => $provinces]);
    }

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

    public function changeBeneficiaryAprIncluded (string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $beneficiary->aprIncluded = !$beneficiary->aprIncluded;

        $beneficiary->save();

        return response()->json(["status" => true, "message" => "Beneficiary apr included changed !"]);
    }
}