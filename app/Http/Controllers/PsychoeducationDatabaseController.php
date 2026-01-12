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
    public function index(Request $request)
    {
        $psychoeducationDatabaseID = Database::where('name', 'psychoeducation_database')->value('id');

        if (!$psychoeducationDatabaseID) {
            return response()->json([
                "status" => false,
                "message" => "psychoeducation database not found",
                "data" => [],
            ], 404);
        }

        $query = Psychoeducations::query()
            ->with([
                'indicator',
                'program' => function ($q) use ($psychoeducationDatabaseID, $request) {
                    $q->where('database_id', $psychoeducationDatabaseID)
                    ->with(['project', 'province', 'district'])

                    ->when($request->filled('projectCode'),
                        fn ($x) => $x->whereHas('project',
                            fn ($p) => $p->where('projectCode', 'like', "%{$request->projectCode}%")
                        )
                    )

                    ->when($request->filled('focalPoint'),
                        fn ($x) => $x->where('focalPoint', $request->focalPoint)
                    )

                    ->when($request->filled('province'),
                        fn ($x) => $x->whereHas('province',
                            fn ($p) => $p->where('name', $request->province)
                        )
                    )

                    ->when($request->filled('siteCode'),
                        fn ($x) => $x->where('siteCode', $request->siteCode)
                    )

                    ->when($request->filled('healthFacilityName'),
                        fn ($x) => $x->where('healthFacilityName', 'like', "%{$request->healthFacilityName}%")
                    )

                    ->when($request->filled('interventionModality'),
                        fn ($x) => $x->where('interventionModality', 'like', "%{$request->interventionModality}%")
                    );
                }
            ])
            ->whereHas('program', fn ($q) =>
                $q->where('database_id', $psychoeducationDatabaseID)
            );

        if ($request->filled('indicator')) {
            $query->whereHas('indicator', function ($q) use ($request) {
                $q->where('indicatorRef', 'like', "%{$request->indicator}%");
            });
        }

        if ($request->filled('awarenessDate')) {
            $query->where('awarenessDate', $request->awarenessDate);
        }

        if ($search = $request->input('search')) {
            $query->where("awarenessTopic", "like", "%{$search}%");
        }

        $psychoeducations = $query->paginate(10);

        if ($psychoeducations->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No psychoeducation found!",
                "data" => [],
            ], 200);
        }

        $psychoeducations->getCollection()->transform(function ($p) {
            return [
                "id" => $p->id,
                "programName" => $p->program?->name,
                "projectCode" => $p->program?->project?->projectCode,
                "province" => $p->program?->province?->name,
                "district" => $p->program?->district?->name,
                "indicator" => $p->indicator?->indicatorRef,
                "awarenessDate" => $p->awarenessDate,
            ];
        });

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $psychoeducations
        ]);
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
