<?php

namespace App\Http\Controllers;
use App\Models\Apr;
use App\Models\Project;

class AprController extends Controller
{
    
    public function generateApr (string $id)
    {

        $apr = Apr::find($id);

        if (!$apr || $apr->status != "firstApproved") return response()->json(["status" => false, "message" => "No such database for approval !"], 404);

        $instance = new AprGeneratorController();

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

        if (!$apr || $apr->status != "firstApproved") return response()->json(["status" => false, "message" => "No such database for reviewing !"], 404);

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

}
