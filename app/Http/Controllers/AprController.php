<?php

namespace App\Http\Controllers;
use App\Models\Apr;

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

}
