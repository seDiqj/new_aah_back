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
    
    public function update(Request $request, $id)
    {

        $outcome = Outcome::find($id);

        if (!$outcome) return response()->json(["status" => false, "message" => "No such outcome in database !"], 404);

        $validated = $request->validate([
            'outcome' => 'required|string',
            'outcomeRef' => 'required|string'
        ]);

        $outcome->update($validated);
        
        return response()->json(['status' => true, 'message' => 'Outcome updated successfully']);
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

