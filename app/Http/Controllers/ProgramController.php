<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Models\Database;
use App\Models\District;
use App\Models\Program;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index (string $databaseName) {

        $database = Database::where("name", $databaseName)->first();

        if (!$database) return response()->json(["status" => false, "message" => "Invalid database selectd !"], 422);

        $programs = Program::with(["project:id,projectCode", "province:id,name", "district:id,name", "database:id,name"])->where("database_id", $database->id)->get()->map(function ($program) {
            return [
                "id" => $program->id,
                "database" => $program->database?->name,
                "name" => $program->name,
                "province" => $program->province?->name,
                "district" => $program->district?->name,
                "projectCode" => $program->project?->projectCode,
                "focalPoint" => $program->focalPoint,
                "village" => $program->village,
                "siteCode" => $program->siteCode,
                "healthFacilityName" => $program->healthFacilityName,
                "interventionModality" => $program->interventionModality
            ];
        });

        if ($programs->isEmpty()) return response()->json(["status" => true, "message" => "No Program found !"],404);

        return response()->json(["status" => true, "message" => "", "data" => $programs]);

    }

    public function store (StoreProgramRequest $request, string $database) {
     
        $data = $request->validated();

        $database = Database::where("name", $database)->first();

        if (!$database) return response()->json(["status" => false, "message" => "Invalid database selected !"], 422);

        $district = District::where("name", $data["district"])->first();

        if (!$district) return response()->json(["status" => false, "message" => "Invalid district selected !"], 422);

        $province = Province::where("name" , $data["province"])->first();

        if (!$province) return response()->json(["status" => false, "message" => "Invalid province selected !"], 422);

        $data["database_id"] = $database->id;
        $data["district_id"] = $district->id;
        $data["province_id"] = $province->id;
        $data["project_id"] = $data["project_id"];

        unset($data["province"]);
        unset($data["district"]);

        $program = Program::create($data);

        if (!$program->exists) return response()->json(["status" => false, "message" => "Somthing gone wrong !"], 500);

        return response()->json(["status" => true, "message" => "Program successfully created !", "data" => $program], 200);
        
    }

    public function show (string $id) {

        $program = Program::with([
            "province:id,name", 
            "database:id,name", 
            "district:id,name", 
            "project:id,projectCode"
        ])->find($id);
        
        if (!$program) {
            return response()->json(["status" => false, "message" => "No such program in system !"], 404);
        }
        
        $data = [
            "id" => $program->id,
            "database" => $program->database->name,
            "province" => $program->province->name,
            "district" => $program->district->name,
            "project_id" => $program->project->id,
            "focalPoint" => $program->focalPoint,
            "village" => $program->village,
            "siteCode" => $program->siteCode,
            "healthFacilityName" => $program->healthFacilityName,
            "interventionModality" => $program->interventionModality
        ];
        
        return response()->json([
            "status" => true,
            "data" => $data
        ]);
        

    }

    public function update (StoreProgramRequest $request, string $id) {

        $program = Program::find($id);

        if (!$program) return response()->json(["status" => false, "message" => "No such program in system !"], 404);

        $data = $request->validated();

        $province = Province::where("name", $data["province"])->first();

        if (!$province) return response()->json(["status" => false, "message" => "Invalid database selected !"], 422);

        $district = District::where("name", $data["district"])->first();

        if (!$district) return response()->json(["status" => false, "message" => "Invalid district selected !"], 422);

        $data["province_id"] = $province->id;
        $data["district_id"] = $district->id;

        unset($data["province"]);
        unset($data["district"]);

        $program->update($data);

        return response()->json(["status" => true, "message" => "Program successfully updated !"], 200);

    }

    public function destroy (Request $request) {

        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer",
        ]);

        $isMoreThenOne = count($ids) > 1 ? true : false;

        Program::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => $isMoreThenOne ? "Programs " : "Program " . "successfully deleted !"], 200);

    }
}
