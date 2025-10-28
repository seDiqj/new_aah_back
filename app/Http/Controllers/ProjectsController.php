<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewAprRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Models\Apr;
use App\Models\Database;
use App\Models\DatabaseProgramBeneficiary;
use App\Models\Dessaggregation;
use App\Models\Enact;
use App\Models\Indicator;
use App\Models\Outcome;
use App\Models\Output;
use App\Models\Project;
use App\Models\ProjectLogs;
use App\Models\Province;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectsController extends Controller
{
    public function indexProjects() 
    {
        $projects = Project::all();

        if ($projects->isEmpty()) return response()->json(["status" => false, "message" => "No project available in database records"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $projects]);
    }

    public function indexProjectsThatHasAtleastOneIndicatorWhichBelongsToSpicificDatabase(string $databaseName)
    {
        error_log("mosa");
        $database = Database::where("name", $databaseName)->first();

        if (!$database) {
            return response()->json([
                "status" => false,
                "message" => "No such database in system with name " . strtoupper($databaseName)
            ], 404);
        }

        $projects = Project::whereHas('outcomes.outputs.indicators', function ($query) use ($database) {
            $query->where('database_id', $database->id);
        })
        ->with(['outcomes.outputs.indicators' => function ($query) use ($database) {
            $query->where('database_id', $database->id);
        }])
        ->get();

        if ($projects->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No projects found with indicators in database " . strtoupper(join(" ", explode("_", $databaseName)))
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $projects
        ]);
    }

    public function indexProjectOutcomes(string $id) 
    {

        $project = Project::with("outcomes")->find($id);
        
        $outcomes = $project->outcomes();

        if (!$outcomes) return response()->json(["status" => false, "message" => "Project has no outcome"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $outcomes]);
    }

    public function indexProjectIndicators(string $id) 
    {

        $project = Project::with('outcomes.outputs.indicators')->find($id);

        $indicators = $project->outcomes
        ->flatMap(fn($outcome) => $outcome->outputs)
        ->flatMap(fn($output) => $output->indicators);

        if (!$indicators) return response()->json(["status" => false, "message" => "Project has no indicator"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $indicators]);

    }

    public function indixProjectSpicificDatabaseIndicator(string $databaseName, string $id)
    {

        if (!($databaseName == "all")) {
            $database = Database::where("name", $databaseName)->first();
            if (!$database) {
                return response()->json([
                    "status" => false,
                    "message" => "Database $databaseName not found!"
                ], 404);
            }
        }

        $project = Project::with('outcomes.outputs.indicators')->find($id);

        if (!$project) {
            return response()->json([
                "status" => false,
                "message" => "No such project in system !"
            ], 404);
        }

        if ($databaseName == "all")

            $indicators = $project->outcomes
                ->flatMap(fn($outcome) => $outcome->outputs)
                ->flatMap(fn($output) => $output->indicators)
                ->values();
        else
        
            $indicators = $project->outcomes
                ->flatMap(fn($outcome) => $outcome->outputs)
                ->flatMap(fn($output) => $output->indicators)
                ->filter(fn($indicator) => $indicator->database_id == $database->id)
                ->values();

        if ($indicators->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No indicators found for this project in database $databaseName!"
            ], 404);
        }

        $indicators = $indicators->map(function ($indicator) {
            $newIndicator = [];

            $newIndicator["id"] = $indicator->id;
            $newIndicator["indicatorRef"] = $indicator->indicatorRef;

            return $newIndicator;
        });

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $indicators
        ]);
    }

    public function indexProjectProvinces (string $id)
    {
        $project = Project::find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in system !"], 404);

        $provinces = $project->provinces()->select("provinces.id", "provinces.name")->get();

        if ($provinces->isEmpty()) return response()->json(["status" => false, "message" => "No province was found for selected project !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $provinces]);
    }

    public function indexProjectLogs (string $id) 
    {
        $project = Project::find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in system !"], 404);

        $logs = $project->logs;

        if ($logs->isEmpty()) return response()->json(["status" => false, "message" => "No log was found for project " . $project->projectCode], 404);

        $logs = $logs->map(function ($log) use ($project) {
            $log["projectCode"] = $project->projectCode;
            $log["userName"] = Auth::user()->name;
            $log["date"] = \Carbon\Carbon::parse($log["created_at"])->diffForHumans();

            unset($log["project_id"], $log["user_id"], $log["created_at"]);

            return $log;
        });

        return response()->json(["status" => true, "message" => "", "data" => $logs], 200);
    }

    public function indexProjectsForSubmitting()
    {


        error_log("project");
        $projects = Project::where("aprStatus", "hqFinalized")->select("id", "projectCode")->get();

        if ($projects->isEmpty()) return response()->json(["status" => false, "message" => "No projects found for submitting \n Note: A project should be finalized by HQ to select for submitting process !"], 404);

        error_log("omdsa");

        return response()->json(["status" => true, "message" => "", "data" => $projects]);

    }

    public function indexProjectNecessaryDataForSubmition (string $id)
    {

        $project = Project::find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in system"], 404);

        $projectProvinces = $project->provinces;

        $projectProvinces = $projectProvinces->map(function ($province) {
            unset($province["pivot"]);
            return $province;
        });

        $projectDatabasesIds = $project->programs()->pluck("database_id")->toArray();

        $projectDatabases = Database::whereIn("id", array_unique($projectDatabasesIds))->get();

        if (Enact::where("project_id", $project->id)->first())
        {
            $enactDatabase = Database::where("name", "enact_database")->first();
            $enactDatabaseData = [
                "id" => $enactDatabase->id,
                "name" => $enactDatabase->name,
            ];

            $projectDatabases->push($enactDatabase);

        }
        $finalData = [
            "provinces" => $projectProvinces,
            "databases" => $projectDatabases,
        ];

        return response()->json(["status" => true, "message" => "", "data" => $finalData]);
    }

    public function indexSubmittedDatabases ()
    {
        $databases = Apr::where("status", "submitted")->get();

        if ($databases->isEmpty()) return response()->json(["status" => false, "message" => "No database with submitted status !"], 404);

        $databases = $databases->map(function ($database) {
            $database["projectCode"] = Project::find($database["project_id"])->projectCode;
            $database["database"] = Database::find($database["database_id"])->name;
            $database["province"] = Province::find($database["province_id"])->name;

            unset($database["project_id"], $database["database_id"], $database["province_id"]);

            return $database;
        });

        return response()->json(["status" => true, "message" => "", "data" => $databases]);
    }

    public function storeProject(StoreProjectRequest $request)
    {
        $validated = $request->validated();

        $validated["aprStatus"] = "notCreatedYet";

        $project = Project::create($validated);

        $thematicSectorNames = $request->input("thematicSector");

        $thematicSectorids = Sector::whereIn("name", $thematicSectorNames)->pluck("id")->toArray();

        $project->sectors()->sync($thematicSectorids);

        $provinceNames = $request->input("provinces");

        $provinceIds = Province::whereIn("name", $provinceNames)->pluck("id")->toArray();

        $project->provinces()->sync($provinceIds);

        return response()->json(["status" => true, "message" => "Project successfully created !", "data" => $project], 200);
    }
    
    public function showProject(string $id) {

        $project = Project::with(["outcomes.outputs.indicators.dessaggregations", "provinces", "sectors"])->find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in database !"], 404);

        $project["outcomesInfo"] = $project->outcomes;

        unset($project["outcomes"]);

        $project->outcomesInfo->map(function ($outcome) {

            $outcome->outputs->map(function ($output) {

                $output->indicators->map(function ($indicator) {

                    $indicator["provinces"] = Indicator::find($indicator["id"])->provinces;
                    $indicator["isp3"] = $indicator->isp3;
                    $indicator["database"] = Database::find($indicator["database_id"])->name;

                    unset($indicator["database_id"]);

                    $indicator["provinces"] = $indicator->provinces->map(function ($province) {

                        $province["province"] = $province["name"];
                        $province["target"] = $province["pivot"]["target"];
                        $province["councilorCount"] = $province["pivot"]["councilorCount"];

                        unset(
                            $province["name"],
                            $province["id"],
                            $province["indicator_id"],
                        );

                        return $province;

                    });

                    $indicator->dessaggregations->map(function ($dessaggregation) use ($indicator) {

                        $dessaggregation["dessaggration"] = $dessaggregation["description"];
                        $dessaggregation["province"] = Province::find($dessaggregation["province_id"])->name;
                        $dessaggregation["indicatorRef"] = $indicator->indicatorRef;
                        $dessaggregation["indicatorId"] = $dessaggregation->indicator_id;

                        unset(
                            $dessaggregation["description"],
                            $dessaggregation["created_at"],
                            $dessaggregation["updated_at"],
                            $dessaggregation["description"],
                            $dessaggregation["province_id"],
                            $dessaggregation["achived_target"],
                            $dessaggregation["id"],
                            // $dessaggregation["indicator_id"],
                            $dessaggregation["months"],
                        );

                        return $dessaggregation;
                    });

                    return $indicator;

                });

                return $output;

            });

            return $outcome;

        });

        $project["provinces"] = $project->provinces->map(function ($province) {

            $provinceName = $province->name;

            return $provinceName;

        });

        return response()->json(["status" => true, "message" => "", "data" => $project]);

    }

    public function updateProject(Request $request, string $id)
    {
        $project = Project::find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in database !"], 404);

        $validated = $request->validate([
            'projectCode'      => ['required', 'string', 'max:255'],
            'projectTitle'     => 'required|string|max:255',
            'projectGoal'      => 'required|string|max:255',
            'projectDonor'     => 'required|string|max:255',
            'startDate'        => 'required|date',
            'endDate'          => 'required|date|after_or_equal:startDate',
            'status'           => 'required|in:planed,ongoing,completed,onhold,canclled',
            'aprStatus'        => 'required|in:notCreatedYet,created,hodDhodApproved,grantFinalized,hqFinalized',
            'projectManager'   => 'required|string|max:255',
            'reportingDate'    => 'required|string|max:255',
            'reportingPeriod'  => 'required|string|max:255',
            'description'      => 'nullable|string',
        ]);

        $project->update($validated);

        return response()->json(["status" => true, "message" => "Project successfully updated !"], 200);
    }

    public function updateProjectFull(Request $request, string $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return response()->json(["status" => false, "message" => "No such project in database!"], 404);
        }

        $validatedProject = $request->validate([
            'projectCode'      => ['required', 'string', 'max:255', Rule::unique('projects', 'projectCode')->ignore($project->id)],
            'projectTitle'     => 'required|string|max:255',
            'projectGoal'      => 'required|string|max:255',
            'projectDonor'     => 'required|string|max:255',
            'startDate'        => 'required|date',
            'endDate'          => 'required|date|after_or_equal:startDate',
            'status'           => 'required|in:planed,ongoing,completed,onhold,canclled',
            'aprStatus'        => 'required|in:NotCreatedYet,created,hodDhodApproved,grantFinalized,hqFinalized',
            'projectManager'   => 'required|string|max:255',
            'reportingDate'    => 'required|string|max:255',
            'reportingPeriod'  => 'required|string|max:255',
            'description'      => 'nullable|string',
            'provinces'        => 'nullable|array',
            'provinces.*'      => 'string',
            'thematicSector'   => 'nullable|array',
            'thematicSector.*' => 'string',
        ]);

        $project->update($validatedProject);

        // 2️⃣ Update Outcomes by ID
        if ($request->has('outcomes') && is_array($request->outcomes)) {
            foreach ($request->outcomes as $outcomeData) {
                if (!empty($outcomeData['id'])) {
                    Outcome::where('id', $outcomeData['id'])
                        ->update(['outcome' => $outcomeData['outcome']]);
                }
            }
        }

        // 3️⃣ Update Outputs by ID
        if ($request->has('outputs') && is_array($request->outputs)) {
            foreach ($request->outputs as $outputData) {
                if (!empty($outputData['id'])) {
                    Output::where('id', $outputData['id'])
                        ->update([
                            'output' => $outputData['output'],
                            'outcome_id' => $outputData['outcome_id'] ?? null
                        ]);
                }
            }
        }

        // 4️⃣ Update Indicators by ID
        if ($request->has('indicators') && is_array($request->indicators)) {
            foreach ($request->indicators as $indicatorData) {
                if (!empty($indicatorData['id'])) {
                    Indicator::where('id', $indicatorData['id'])
                            ->update([
                                'indicator' => $indicatorData['indicator'],
                                'output_id' => $indicatorData['output_id'] ?? null,
                                'dessaggregationType' => $indicatorData['dessaggregationType'] ?? null,
                            ]);
                }
            }
        }

        // 5️⃣ Update Dessaggregations by ID
        if ($request->has('dessaggregations') && is_array($request->dessaggregations)) {
            foreach ($request->dessaggregations as $dessData) {
                if (!empty($dessData['id'])) {
                    Dessaggregation::where('id', $dessData['id'])
                                ->update([
                                    'description' => $dessData['description'],
                                    'target' => $dessData['target'],
                                ]);
                }
            }
        }

        return response()->json([
            "status" => true,
            "message" => "Project and all related data successfully updated!"
        ], 200);
    }

    public function destroyProject(Request $request) 
    {
        
        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        Project::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Project successfully deleted !"], 200);
    }

    public function destroySubmittedDatabase (Request $request)
    {
        $request->validate([
            "ids" => "required|array",
            "ids.*" => "required|integer"
        ]);

        $ids = $request->ids;

        Apr::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Submitted databases successfully deleted from submitted databases table !"], 200);
    }

    public function changeAprStatus (Request $request, string $id) 
    {
        $validated = $request->validate([
            'newStatus' => 'required|in:notCreatedYet,created,hodDhodApproved,hodDhodRejected,grantFinalized,grantRejected,hqFinalized,hqRejected',
            'comment' => 'nullable|string',
        ]);

        $userId = Auth::user()->id;

        if (!$userId) return response()->json(["status" => false, "message" => "Forbiden Unauthinticated !"], 403);

        $project = Project::find($id);

        if (!$project) return response()->json(["status" => false, "message" => "No such project in system !"], 404);

        $newStatus = $validated["newStatus"];

        $project->aprStatus = $newStatus;

        $project->save();

        $action = null;

        switch ($newStatus) {
            case 'notCreatedYet':
                $action = 'reset';
                break;

            case 'created':
                $action = 'create';
                break;

            case 'hodDhodApproved':
                $action = 'submit';
                break;

            case 'hodDhodRejected':
                $action = 'rejectSubmit';
                break;

            case 'grantFinalized':
                $action = 'grantFinalize';
                break;

            case 'grantRejected':
                $action = 'rejectGrant';
                break;

            case 'hqFinalized':
                $action = 'hqFinalize';
                break;

            case 'hqRejected':
                $action = 'rejectHq';
                break;

            default:
                $action = 'unknown';
                break;
        }


        ProjectLogs::create([
            "project_id" => $project->id,
            "user_id" => $userId,
            "action" => $action,
            "comment" => $validated["comment"],
            "result" => array_key_exists("result", $validated) ? $validated["result"] : null,
        ]);

        return response()->json(["status" => true, "message" => "Project APR status updated successfully. \n Check the logs!"]);
        
    }

    public function getProjectFinalizersDetails(string $id)
    {
        $logs = ProjectLogs::with('user')
            ->where('project_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();

        $finalizers = $logs->map(function ($log) {
            return [
                'user_id' => $log->user_id,
                'name' => $log->user ? $log->user->name : null,
                'avatar' => $log->user ? url("storage/" . $log->user->photo_path) : null, // <<< اینجا
                'action' => $log->action,
                'comment' => $log->comment,
                'result' => $log->result,
                'step' => $log->project_step ?? null,
                'date' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $finalizers,
        ]);
    }

}
