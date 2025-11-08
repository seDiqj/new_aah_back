<?php

namespace App\Http\Controllers;

use App\Models\District;
use App\Models\Indicator;
use App\Models\Program;
use App\Models\Project;
use App\Models\Province;
use App\Models\User;
use App\Models\Beneficiary;
use Illuminate\Http\Request;

class GlobalController extends Controller
{

    public function indexBeneficiaryIndicators(string $id) {}

    public function indexDatabasePrograms(string $db) {}

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
        })->select("indicator")->get();

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

    public function storeProgram(Request $request, string $db) {}

    public function showProgram(string $db) {}

    public function updateProgram(Request $request, string $db, string $id ) {}

    public function destroyProgram(Request $request, string $db, string $id) {}

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

    public function changeBeneficiaryAprIncluded (string $id)
    {
        $beneficiary = Beneficiary::find($id);

        if (!$beneficiary) return response()->json(["status" => false, "message" => "No such beneficiary in system !"], 404);

        $beneficiary->aprIncluded = !$beneficiary->aprIncluded;

        $beneficiary->save();

        return response()->json(["status" => true, "message" => "Beneficiary apr included changed !"]);
    }
}