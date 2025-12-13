<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnactRequest;
use App\Models\Assessment;
use App\Models\Enact;
use App\Models\Indicator;
use App\Models\Project;
use App\Models\Province;
use App\Models\Question;
use Illuminate\Http\Request;

class EnactController extends Controller
{
    public function index () 
    {
        $enacts = Enact::all();

        if ($enacts->isEmpty()) return response()->json(["status" => false, "message" => 'No assessment was found in system !'], 404);
        
        $enacts->map(function ($enact) {
            $enact["projectCode"] = Project::find($enact["project_id"])->projectCode;
            $enact["province"] = Province::find($enact["province_id"])->name;
            $enact["indicatorRef"] = Indicator::find($enact["indicator_id"])->indicatorRef;

            unset($enact["project_id"], $enact["province_id"], $enact["indicator_id"]);

            return $enact;
        });

        return response()->json(["status" => true, "message" => "", "data" => $enacts]);
    }

    public function indexAssessmentsList ()
    {
        $Questions = Question::all();

        if ($Questions->isEmpty()) return response()->json(["status" => false, "message" => "No Questions found in system !"], 404);

        $finalData = $Questions->groupBy("group");

        return response()->json(["status" => true, "message" => "", "data" => $finalData]);
    }

    public function store (StoreEnactRequest $request)
    {
        $validated = $request->validated();

        Enact::create($validated);

        return response()->json(["status" => true, "message" => "Assessment successfully saved !"], 200);
    }


    public function update (StoreEnactRequest $request, string $id)
    {
        $enact = Enact::find($id);

        if (!$enact) return response()->json(["status" => false, "message" => "No such assessment in system !"], 404);

        $validated = $request->validated();

        $enact->update($validated);

        return response()->json(["status" => true, "message" => "Assessment successfully updated !"], 200);
    }

    public function showForProfile (string $id)
    {
        $enact = Enact::find($id);

        if (!$enact) return response()->json(["status" => false, "message" => "No such assessment in system !"], 404);

        $enact["projectCode"] = Project::find($enact["project_id"])->projectCode;
        $enact["Indicator Reference"] = Indicator::find($enact["indicator_id"])->indicatorRef;
        $enact["Province"] = Province::find($enact["province_id"])->name;

        unset($enact["project_id"], $enact["indicator_id"], $enact["province_id"]);

        $assessments = $enact->assessments()->select("id")->get();

        // if ($assessments->isEmpty()) return response()->json(["status" => false, "message" => "No assess for current assessment !"], 404);

        $enact["assessments"] = $assessments;

        return response()->json(["status" => true, "message" => "" , "data" => $enact]);
    }

    public function show (string $id)
    {
        $enact = Enact::find($id);

        if (!$enact) return response()->json(["status" => false, "message" => "No such assessment in system !"], 404);

        return response()->json(["status" => true, "message" => "" , "data" => $enact]);
    }

    public function showAssessment (string $id)
    {
        $assessment = Assessment::find($id);

        if (!$assessment) return response()->json(["status" => false, "message" => "No such assessment in the system !"], 404);

        $questions = $assessment->questions;

        $finalData = [
            "assessment" => $assessment,
            "questions" => $questions
        ];

        return response()->json(["status" => true, "message" => "", "data" => $finalData]);
    }

    public function updateAssessmentScore (string $id) {

        $assessmentScores = Assessment::with("questions")->find($id);

        if (!$assessmentScores) return response()->json(["status" => false, "message" => "No such assessment in system !"], 404);

        $assessmentScores->questions->map(function ($q) {
            $q->score = $q->pivot->score;
            unset(
                $q->id,
                $q->group,
                $q->description,
                $q->created_at,
                $q->updated_at,
                $q->pivot
            );

            return $q;
        });

        unset(
            $assessmentScores->id,
            $assessmentScores->enact_id
        );

        return response()->json(["status" => true, "message" => "", "data" => $assessmentScores], 200);

    }

    public function destroyAssessmentScore (string $id) {

    }

    public function showAssessmentScore (string $id) {

    }

    public function destroy (Request $request)
    {
        $validated = $request->validate([
            "ids" => "required|array",
            "ids.*" => "required|integer"
        ]);

        $ids = $validated["ids"];

        Enact::whereIn("id", $ids)->delete();

        return response()->json(["status" => false, "message" => "Assessments successfully deleted !"], 200);
    }

    public function destroyAssessment(string $id) {
        $assessment = Assessment::find($id);

        if (!$assessment) return response()->json(["status" => false, "message" => "No such assessment in system !"], 404);

        $assessment->forceDelete();

        return response()->json(["status" => true, "message" => "Assessment successfully deleted !"], 200);
    }

    public function assessAssessment(Request $request)
    {

        $enact = Enact::findOrFail($request->enactId);
        $scores = $request->input('scores');
        $date = $request->input("date");
    
        $questionIds = array_keys($scores);
    
        $count = Question::whereIn('id', $questionIds)->count();
        if ($count !== count($questionIds)) {
            return response()->json(['message' => 'Some question IDs are invalid'], 422);
        }

        $assessment = $enact->assessments()->create([
            "totalScore" => 0,
            "date" => $date
        ]);

        $totalScore = 0;
        
        $pivotData = [];
        foreach ($questionIds as $questionId) {
            $pivotData[$questionId] = ['score' => $scores[$questionId]];
            $totalScore += (int) $scores[$questionId];
        }


        $assessment->update([
            "totalScore" => $totalScore
        ]);

        $assessment->questions()->syncWithoutDetaching($pivotData);
    
        return response()->json(['message' => 'Scores saved successfully']);
    }

} 