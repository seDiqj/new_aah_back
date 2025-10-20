<?php

namespace App\Http\Controllers;

use App\Http\Requests\PsychoeducationFormRequest;
use App\Models\Database;
use App\Models\Indicator;
use App\Models\Program;
use App\Models\Psychoeducations;
use Illuminate\Http\Request;

class PsychoeducationDatabaseController extends Controller
{
    public function index () 
    {
        $psychoeducations = Psychoeducations::select("id", "program_id", "indicator_id", "awarenessTopic", "awarenessDate")->get();

        if ($psychoeducations->isEmpty()) return response()->json(["status" => false, "message" => "No psychoeducation found !"], 404);

        $psychoeducations->map(function ($p) {
            $p["program"] = Program::find($p["program_id"])->focalPoing;
            $p["indicator"] = Indicator::find($p["indicator_id"])->indicatorRef;

            unset($p["program_id"], $p["indicator_id"]);

            return $p;
        });

        return response()->json(["status" => true, "message" => "", "data" => $psychoeducations]);
    }

    public function store (PsychoeducationFormRequest $request)
    {

        $psychoeducationDatabase = Database::where("name", "psychoeducation_database")->first();

        if (!$psychoeducationDatabase) return response()->json(["status" => false, "message" => "Psychoeducation is not a database !"], 404);

        $psychoeducationDatabaseId = $psychoeducationDatabase->id;

        $programInformations = $request->input("programInformation");

        $indicatorId = $programInformations["indicator_id"];
 
        $programInformations["database_id"] = $psychoeducationDatabaseId;

        $program = Program::create($programInformations);

        $psychoeducationInformations = $request->input("psychoeducationInformation");

        $psychoeducationInformations["indicator_id"] = $indicatorId;

        $program->psychoeducation()->updateOrCreate($psychoeducationInformations);

        return response()->json(["status" => false, "message" => "Psychoeducation successfully created !"], 200);
    }

    public function show (string $id)
    {
        $psychoeducation = Psychoeducations::find($id);

        if (!$psychoeducation) return response()->json(["status" => false, "message" => "No such psychoeducation in system !"], 404);

        $program = $psychoeducation->program;

        $program["indicator_id"] = $psychoeducation["indicator_id"];

        $finalData = [
            "programData" => $program,
            "psychoeducationData" => $psychoeducation,
        ];

        return response()->json(["status" => true, "message" => "", "data" => $finalData]);

        
    }

    public function update(PsychoeducationFormRequest $request, $id)
    {
        $psychoeducation = Psychoeducations::find($id);
        if (!$psychoeducation) {
            return response()->json([
                "status" => false,
                "message" => "No such psychoeducation in system!"
            ], 404);
        }

        $programInformations = $request->input("programInformation");
        $indicatorId = $programInformations["indicator_id"];

        $program = $psychoeducation->program;
        if ($program) {
            $program->update($programInformations);
        } else {
            $psychoeducationDatabase = Database::where("name", "psychoeducation_database")->first();
            if (!$psychoeducationDatabase) {
                return response()->json([
                    "status" => false,
                    "message" => "Psychoeducation is not a database!"
                ], 404);
            }
            $programInformations["database_id"] = $psychoeducationDatabase->id;
            $program = Program::create($programInformations);
            $psychoeducation->program_id = $program->id;
        }

        $psychoeducationInformations = $request->input("psychoeducationInformation");
        $psychoeducationInformations["indicator_id"] = $indicatorId;

        $psychoeducation->update($psychoeducationInformations);

        return response()->json([
            "status" => true,
            "message" => "Psychoeducation successfully updated!"
        ], 200);
    }


    public function destroy (Request $request)
    {
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        Psychoeducations::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Psychoeducations successfully deleted !"], 200);
    }
}
