<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutcomeRequest;
use App\Models\Outcome;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OutcomeController extends Controller
{

    public function index(string $id)
    {
        $outcomes = Outcome::where("project_id", $id)->get();
    
        if ($outcomes->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No outcome for your project!"
            ], 404);
        }
    
        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $outcomes
        ]);
    }
    
    public function store(StoreOutcomeRequest $request)
    {

        $validated = $request->validated();

        $projectId = $request["project_id"];

        $createdOutcomes = [];

        foreach ($validated["outcomes"] as $outcome) {
            $outcome["project_id"] = $projectId;
            
            $createdOutcome = Outcome::create($outcome);

            array_push($createdOutcomes, ["id" => $createdOutcome->id, "outcomeRef" => $createdOutcome->outcomeRef]);
        }


        return response()->json(["status" => true, "message" => "Outcomes successfully saved !", "data" => $createdOutcomes]);

    }
    
    public function updateOutcomes(Request $request, $id)
    {
        $project = Project::find($id);
        if (!$project) return response()->json(['status' => false, 'message' => 'Project not found'], 404);

        $request->validate([
            'outcomes' => 'required|array',
            'outcomes.*.outcome' => 'required|string',
            'outcomes.*.outcomeRef' => 'required|string',
        ]);

        foreach ($request->outcomes as $outcomeData) {
            Outcome::updateOrCreate(
                ['project_id' => $project->id, 'outcomeRef' => $outcomeData['outcomeRef']],
                ['outcome' => $outcomeData['outcome']]
            );
        }

        return response()->json(['status' => true, 'message' => 'Outcomes updated successfully']);
    }


    public function destroy(string $id)
    {
        $outcome = Outcome::find($id);

        if (!$outcome) return response()->json(["status" => false, "message" => "No such outcome in system !"], 404);

        $outcome->delete();

        return response()->json([
            'status' => true,
            'message' => 'Outcome successfully deleted !'
        ]);
    }
}

