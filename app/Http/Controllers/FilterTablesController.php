<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Program;
use App\Models\Indicator;
use Illuminate\Http\Request;

class FilterTablesController extends Controller
{
    public function filterProjects(Request $request)
    {
        $query = Project::query();

        // projectCode
        if ($request->filled('projectCode')) {
            $query->where('projectCode', 'like', '%' . $request->projectCode . '%');
        }

        // projectManager
        if ($request->filled('projectManager')) {
            $query->where('projectManager', $request->projectManager);
        }

        // startDate - endDate
        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('startDate', [$request->startDate, $request->endDate]);
        } elseif ($request->filled('startDate')) {
            $query->where('startDate', '>=', $request->startDate);
        } elseif ($request->filled('endDate')) {
            $query->where('endDate', '<=', $request->endDate);
        }

        if ($request->filled('reportingDate')) {
            $query->where('reportingDate', $request->reportingDate);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('aprStatus')) {
            $query->where('aprStatus', $request->aprStatus);
        }

        if ($request->filled('projectTitle')) {
            $query->where('projectTitle', 'like', '%' . $request->projectTitle . '%');
        }

        if ($request->filled('projectDonor')) {
            $query->where('projectDonor', 'like', '%' . $request->projectDonor . '%');
        }

        if ($request->filled('projectGoal')) {
            $query->where('projectGoal', 'like', '%' . $request->projectGoal . '%');
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No project available in database records",
                "data" => [],
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $projects,
        ]);
    }

    public function filterMainDbBnf(Request $request)
    {
        $mainDatabaseId = \App\Models\Database::where('name', 'main_database')->value('id');

        if (!$mainDatabaseId) {
            return response()->json([
                "status" => false,
                "message" => "Main database not found",
                "data" => [],
            ], 404);
        }

        $query = \App\Models\Beneficiary::query()
            ->with(['programs.project', 'indicators', 'mealTools']);

        $query->whereHas('programs', function($q) use ($request, $mainDatabaseId) {
            $q->where('database_program_beneficiary.database_id', $mainDatabaseId);

            if ($request->filled('projectCode')) {
                $q->whereHas('project', function($q2) use ($request) {
                    $q2->where('projectCode', 'like', '%' . $request->projectCode . '%');
                });
            }

            if ($request->filled('focalPoint')) {
                $q->where('focalPoint', $request->focalPoint);
            }

            if ($request->filled('province')) {
                $q->where('province_id', $request->province);
            }

            if ($request->filled('siteCode')) {
                $q->where('siteCode', $request->siteCode);
            }

            if ($request->filled('healthFacilitator')) {
                $q->where('healthFacilityName', 'like', '%' . $request->healthFacilitator . '%');
            }
        });

        if ($request->filled('dateOfRegistration')) {
            $query->where('dateOfRegistration', $request->dateOfRegistration);
        }

        if ($request->filled('age')) {
            $query->where('age', $request->age);
        }

        if ($request->filled('maritalStatus')) {
            $query->where('maritalStatus', $request->maritalStatus);
        }

        if ($request->filled('householdStatus')) {
            $query->where('householdStatus', 'like', '%' . $request->householdStatus . '%');
        }

        if ($request->filled('baselineDate')) {
            $query->whereHas('mealTools', function($q) use ($request) {
                $q->where('baselineDate', $request->baselineDate);
            });
        }

        if ($request->filled('endlineDate')) {
            $query->whereHas('mealTools', function($q) use ($request) {
                $q->where('endlineDate', $request->endlineDate);
            });
        }

        if ($request->filled('indicator')) {
            $query->whereHas('indicators', function($q) use ($request) {
                $q->where('indicator', 'like', '%' . $request->indicator . '%');
            });
        }

        $beneficiaries = $query->get();

        if ($beneficiaries->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No beneficiary found in database records",
                "data" => [],
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $beneficiaries,
        ]);
    }

    public function filterMainDbPrograms(Request $request)
    {
        $mainDatabaseId = \App\Models\Database::where('name', 'main_database')->value('id');

        if (!$mainDatabaseId) {
            return response()->json([
                "status" => false,
                "message" => "Main database not found",
                "data" => [],
            ], 404);
        }

        $query = \App\Models\Program::query()
            ->with(['project']);

        $query->where('database_id', $mainDatabaseId);

        if ($request->filled('projectCode')) {
            $query->whereHas('project', function($q) use ($request) {
                $q->where('projectCode', 'like', '%' . $request->projectCode . '%');
            });
        }

        if ($request->filled('focalPoint')) {
            $query->where('focalPoint', $request->focalPoint);
        }

        if ($request->filled('province')) {
            $provinceId = \App\Models\Province::where('name', $request->province)->value('id');
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
        }

        if ($request->filled('district')) {
            $districtId = \App\Models\District::where('name', $request->district)->value('id');
            if ($districtId) {
                $query->where('district_id', $districtId);
            }
        }


        if ($request->filled('village')) {
            $query->where('village', 'like', '%' . $request->village . '%');
        }

        if ($request->filled('siteCode')) {
            $query->where('siteCode', $request->siteCode);
        }

        if ($request->filled('healthFacilityName')) {
            $query->where('healthFacilityName', 'like', '%' . $request->healthFacilityName . '%');
        }

        if ($request->filled('interventionModality')) {
            $query->where('interventionModality', 'like', '%' . $request->interventionModality . '%');
        }

        // اجرای query
        $programs = $query->get();

        if ($programs->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No program found in database records",
                "data" => [],
            ], 404);
        }

        $programs = $programs->map(function ($program) {
            return [
                "id" => $program->id,
                "database" => $program->database->name,
                "province" => $program->province->name,
                "district" => $program->district->name,
                "projectCode" => $program->project->projectCode,
                "focalPoint" => $program->focalPoint,
                "village" => $program->village,
                "siteCode" => $program->siteCode,
                "healthFacilityName" => $program->healthFacilityName,
                "interventionModality" => $program->interventionModality
            ];
        });

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $programs,
        ]);
    }

    public function filterKitDbPrograms(Request $request)
    {
        $kitDatabaseId = \App\Models\Database::where('name', 'kit_database')->value('id');

        if (!$kitDatabaseId) {
            return response()->json([
                "status" => false,
                "message" => "Kit database not found",
                "data" => [],
            ], 404);
        }

        $query = \App\Models\Program::query()
            ->with(['project']);

        $query->where('database_id', $mainDatabaseId);

        if ($request->filled('projectCode')) {
            $query->whereHas('project', function($q) use ($request) {
                $q->where('projectCode', 'like', '%' . $request->projectCode . '%');
            });
        }

        if ($request->filled('focalPoint')) {
            $query->where('focalPoint', $request->focalPoint);
        }

        if ($request->filled('province')) {
            $provinceId = \App\Models\Province::where('name', $request->province)->value('id');
            if ($provinceId) {
                $query->where('province_id', $provinceId);
            }
        }

        if ($request->filled('district')) {
            $districtId = \App\Models\District::where('name', $request->district)->value('id');
            if ($districtId) {
                $query->where('district_id', $districtId);
            }
        }


        if ($request->filled('village')) {
            $query->where('village', 'like', '%' . $request->village . '%');
        }

        if ($request->filled('siteCode')) {
            $query->where('siteCode', $request->siteCode);
        }

        if ($request->filled('healthFacilityName')) {
            $query->where('healthFacilityName', 'like', '%' . $request->healthFacilityName . '%');
        }

        if ($request->filled('interventionModality')) {
            $query->where('interventionModality', 'like', '%' . $request->interventionModality . '%');
        }

        // اجرای query
        $programs = $query->get();

        if ($programs->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No program found in database records",
                "data" => [],
            ], 404);
        }

        $programs = $programs->map(function ($program) {
            return [
                "id" => $program->id,
                "database" => $program->database->name,
                "province" => $program->province->name,
                "district" => $program->district->name,
                "projectCode" => $program->project->projectCode,
                "focalPoint" => $program->focalPoint,
                "village" => $program->village,
                "siteCode" => $program->siteCode,
                "healthFacilityName" => $program->healthFacilityName,
                "interventionModality" => $program->interventionModality
            ];
        });

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $programs,
        ]);
    }

    public function filterPsychoeducations(Request $request)
    {
        $psychoeducationDatabaseID = \App\Models\Database::where('name', 'psychoeducation_database')->value('id');

        if (!$psychoeducationDatabaseID) {
            return response()->json([
                "status" => false,
                "message" => "psychoeducation database not found",
                "data" => [],
            ], 404);
        }

        $query = \App\Models\Psychoeducations::query()
            ->with(['program.project', 'program.province', 'program.district']);

        $query->whereHas('program', function($q) use ($psychoeducationDatabaseID, $request) {
            $q->where('database_id', $psychoeducationDatabaseID);

            if ($request->filled('projectCode')) {
                $q->whereHas('project', function($q2) use ($request) {
                    $q2->where('projectCode', 'like', '%' . $request->projectCode . '%');
                });
            }

            if ($request->filled('focalPoint')) {
                $q->where('focalPoint', $request->focalPoint);
            }

            if ($request->filled('province')) {
                $provinceId = \App\Models\Province::where('name', $request->province)->value('id');
                if ($provinceId) {
                    $q->where('province_id', $provinceId);
                }
            }

            if ($request->filled('siteCode')) {
                $q->where('siteCode', $request->siteCode);
            }

            if ($request->filled('healthFacilityName')) {
                $q->where('healthFacilityName', 'like', '%' . $request->healthFacilityName . '%');
            }

            if ($request->filled('interventionModality')) {
                $q->where('interventionModality', 'like', '%' . $request->interventionModality . '%');
            }
        });

        if ($request->filled('indicator')) {
            $query->where('indicator_id', function($q) use ($request) {
                $q->from('indicators')->select('id')->where('indicator', 'like', '%' . $request->indicator . '%');
            });
        }

        if ($request->filled('awarenessDate')) {
            $query->where('awarenessDate', $request->awarenessDate);
        }

        $psychoeducations = $query->get();

        if ($psychoeducations->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No psychoeducation records found",
                "data" => [],
            ], 404);
        }

        $psychoeducations = $psychoeducations->map(function ($p) {
            return [
                'id' => $p->id,
                'program' => $p->program->focalPoint ?? null,
                'indicator' => Indicator::find($p->indicator_id)->indicatorRef ?? null,
                'awarenessDate' => $p->awarenessDate,
                'awarenessTopic' => $p->awarenessTopic,
            ];
        })->values(); 

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $psychoeducations,
        ]);
    }

    public function filterCdDbBnf(Request $request)
    {
        $cdDatabaseID = \App\Models\Database::where('name', 'cd_database')->value('id');

        if (!$cdDatabaseID) {
            return response()->json([
                "status" => false,
                "message" => "Main database not found",
                "data" => [],
            ], 404);
        }

        $query = \App\Models\Beneficiary::query()
            ->with(['programs.project', 'indicators', 'mealTools']);

        $query->whereHas('programs', function($q) use ($request, $cdDatabaseID) {
            $q->where('database_program_beneficiary.database_id', $cdDatabaseID);

            if ($request->filled('projectCode')) {
                $q->whereHas('project', function($q2) use ($request) {
                    $q2->where('projectCode', 'like', '%' . $request->projectCode . '%');
                });
            }

            if ($request->filled('focalPoint')) {
                $q->where('focalPoint', $request->focalPoint);
            }

            if ($request->filled('province')) {
                $q->where('province_id', $request->province);
            }

            if ($request->filled('siteCode')) {
                $q->where('siteCode', $request->siteCode);
            }

            if ($request->filled('healthFacilitator')) {
                $q->where('healthFacilityName', 'like', '%' . $request->healthFacilitator . '%');
            }
        });

        if ($request->filled('dateOfRegistration')) {
            $query->where('dateOfRegistration', $request->dateOfRegistration);
        }

        if ($request->filled('age')) {
            $query->where('age', $request->age);
        }

        if ($request->filled('maritalStatus')) {
            $query->where('maritalStatus', $request->maritalStatus);
        }

        if ($request->filled('householdStatus')) {
            $query->where('householdStatus', 'like', '%' . $request->householdStatus . '%');
        }

        if ($request->filled('baselineDate')) {
            $query->whereHas('mealTools', function($q) use ($request) {
                $q->where('baselineDate', $request->baselineDate);
            });
        }

        if ($request->filled('endlineDate')) {
            $query->whereHas('mealTools', function($q) use ($request) {
                $q->where('endlineDate', $request->endlineDate);
            });
        }

        if ($request->filled('indicator')) {
            $query->whereHas('indicators', function($q) use ($request) {
                $q->where('indicator', 'like', '%' . $request->indicator . '%');
            });
        }

        $beneficiaries = $query->get();

        if ($beneficiaries->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No beneficiary found in database records",
                "data" => [],
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $beneficiaries,
        ]);
    }


}
