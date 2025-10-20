<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIndicatorRequest;
use App\Models\Database;
use App\Models\Indicator;
use App\Models\IndicatorType;
use App\Models\Isp3;
use App\Models\Output;
use App\Models\Project;
use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IndicatorController extends Controller
{
    public function index(string $id) {

        $project = Project::where("id", $id)->first();

        if (!$project) return response()->json(["status" => false, "message" => "No such project in system"], 404);

        $indicators = $project->indicators;

        if ($indicators->isEmpty()) return response()->json(["status" => false, "message" => "No indicator was found for this project !"], 404);

        return response()->json(["status" => true, "message" => "", "data" => $indicators]);

    }

    public function store(Request $request)
    {
        $indicators = $request->input('indicators');

        if (!is_array($indicators)) {
            return response()->json([
                'status' => false,
                'message' => 'Indicators data must be an array.'
            ], 422);
        }

        $createdIndicators = [];

        foreach ($indicators as $indicator) {

            if (!is_array($indicator)) continue;
            
            $output = Output::find($indicator["outputId"]);

            if (!$output) return response()->json(["status" => false, "message" => "Invalid output ref for indicator with " . $indicator["indicatorRef"] . " reference!"], 404);

            $database = Database::where("name", $indicator["database"])->first();
            if (!$database) return response()->json(["status" => false, "message" => "Invalid database for indicator with " . $indicator["indicatorRef"] . " reference!"], 404);

            $typeId = null;
            if (!empty($indicator["type"])) {
                $dbType = IndicatorType::where("type", $indicator["type"])->first();
                if (!$dbType) return response()->json(["status" => false, "message" => "Invalid type selected for indicator with reference " . $indicator["indicatorRef"]], 404);
                $typeId = $dbType->id;
            }

            $main = Indicator::create([
                'output_id' => $output->id,
                'database_id' => $database->id,
                'type_id' => $typeId,
                'indicator' => $indicator['indicator'],
                'indicatorRef' => $indicator['indicatorRef'],
                'target' => $indicator['target'],
                'achived_target' => 0,
                'status' => $indicator['status'],
                'dessaggregationType' => $indicator['dessaggregationType'],
                'description' => $indicator['description'],
                'parent_indicator' => null,
            ]);

            array_push($createdIndicators, [
                "id" => $main->id,
                "indicatorRef" => $main->indicatorRef
            ]);

            $provincesDetails = $indicator["provinces"] ?? [];
            $provincesNames = collect($provincesDetails)->pluck('province')->toArray();
            $provincesIds = Province::whereIn("name", $provincesNames)->pluck("id", "name")->toArray();
            
            $finalProvincesData = [];
            foreach ($provincesDetails as $provinceData) {
                $provinceName = strtolower($provinceData["province"]);
                if (!isset($provincesIds[$provinceName])) continue;
                $finalProvincesData[$provincesIds[$provinceName]] = [
                    "target" => $provinceData["target"],
                    "achived_target" => 0,
                    "councilorCount" => $provinceData["councilorCount"]
                ];
            }

            

            $main->provinces()->sync($finalProvincesData);

            if (!empty($indicator['subIndicator'])) {
                $sub = $indicator['subIndicator'];

                $createdSub = Indicator::create([
                    'output_id' => $output->id,
                    'database_id' => $database->id,
                    'type_id' => null,
                    'indicator' => $sub['name'],
                    'indicatorRef' => $sub['indicatorRef'],
                    'target' => $sub['target'] ?? $indicator['target'],
                    'achived_target' => 0,
                    'status' => $indicator['status'],
                    'dessaggregationType' => $sub['dessaggregationType'],
                    'description' => $indicator['description'],
                    'parent_indicator' => $main->id,
                ]);

                array_push($createdIndicators, [
                    "id" => $createdSub->id,
                    "indicatorRef" => $createdSub->indicatorRef
                ]);

                $subProvinces = $sub["provinces"] ?? [];
                $finalSubData = [];
                foreach ($subProvinces as $provinceData) {
                    $provinceName = strtolower($provinceData["province"]);
                    if (!isset($provincesIds[$provinceName])) continue;
                    $finalSubData[$provincesIds[$provinceName]] = [
                        "target" => $provinceData["target"],
                        "achived_target" => 0,
                        "councilorCount" => $provinceData["councilorCount"]
                    ];
                }

                $createdSub->provinces()->sync($finalSubData);
            }
        }

        return response()->json(["status" => true, 'message' => 'Indicators saved successfully.', "data" => $createdIndicators], 201);
    }

    protected function validateIndicator(array $data)
    {
        $rules = (new StoreIndicatorRequest)->rulesForSingleIndicator();

        return Validator::make($data, $rules)->validate();
    }

    public function show($id)
    {
        $indicator = Indicator::with('subIndicators')->find($id);

        if (!$indicator) {
            return response()->json([
                'status' => false,
                'message' => 'Indicator not found.'
            ], 404);
        }

        $data = [
            'outputRef' => $indicator->output->outputRef,
            'indicator' => $indicator->indicator,
            'indicatorRef' => $indicator->indicatorRef,
            'target' => $indicator->target,
            'status' => $indicator->status,
            'provinces' => $indicator->provinces,
            'dessaggregationType' => $indicator->dessaggregationType,
            'description' => $indicator->description,
            'subIndicator' => $indicator->subIndicators->map(function($sub) use ($indicator) {
                return [
                    'indicatorRef' => $sub->indicatorRef,
                    'name' => $sub->indicator,
                    'target' => $sub->target ?? $indicator->target,
                    'dessaggregationType' => $sub->dessaggregationType ?? $indicator->dessaggregationType,
                    'provinces' => $sub->provinces,
                ];
            })->first() 
        ];

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $indicator = Indicator::find($id);

        if (!$indicator) {
            return response()->json([
                'status' => false,
                'message' => 'Indicator not found.'
            ], 404);
        }

        // 🔹 پیدا کردن Output و Database
        $output = Output::where("outputRef", $request->input("outputRef", $indicator->output->outputRef))->first();
        if (!$output) {
            return response()->json(["status" => false, "message" => "Invalid output ref!"], 404);
        }

        $database = Database::where('name', $request->input("database", $indicator->database->name))->first();
        if (!$database) {
            return response()->json(["status" => false, "message" => "Invalid database!"], 404);
        }

        // 🔹 آپدیت اندیکیتور اصلی
        $indicator->update([
            'output_id' => $output->id,
            'database_id' => $database->id,
            'indicator' => $request->input('indicator', $indicator->indicator),
            'indicatorRef' => $request->input('indicatorRef', $indicator->indicatorRef),
            'target' => $request->input('target', $indicator->target),
            'achived_target' => $request->input('achived_target', $indicator->achived_target),
            'status' => $request->input('status', $indicator->status),
            'dessaggregationType' => $request->input('dessaggregationType', $indicator->dessaggregationType),
            'description' => $request->input('description', $indicator->description),
        ]);

        // 🔹 آپدیت provinces (در جدول pivot)
        $provincesDetails = $request->input("provinces", []);
        if (is_array($provincesDetails) && count($provincesDetails) > 0) {
            $provincesNames = collect($provincesDetails)->pluck('province')->toArray();
            $provincesIds = Province::whereIn("name", $provincesNames)
                ->pluck("id", "name")->toArray();

            $finalProvincesData = [];
            foreach ($provincesDetails as $provinceData) {
                $provinceName = strtolower($provinceData["province"]);
                if (!isset($provincesIds[$provinceName])) continue;
                $finalProvincesData[$provincesIds[$provinceName]] = [
                    "target" => $provinceData["target"],
                    "achived_target" => $provinceData["achived_target"] ?? 0,
                    "councilorCount" => $provinceData["councilorCount"] ?? 0,
                ];
            }

            $indicator->provinces()->sync($finalProvincesData);
        }

        // 🔹 آپدیت subIndicator در صورت وجود
        if ($request->has('subIndicator')) {
            $sub = $request->input('subIndicator');

            $subIndicator = Indicator::firstOrNew([
                'indicatorRef' => $sub['indicatorRef'],
                'parent_indicator' => $indicator->id
            ]);

            $subIndicator->fill([
                'output_id' => $output->id,
                'database_id' => $database->id,
                'indicator' => $sub['name'],
                'target' => $sub['target'] ?? $indicator->target,
                'achived_target' => $subIndicator->achived_target ?? 0,
                'status' => $sub['status'] ?? $indicator->status,
                'dessaggregationType' => $sub['dessaggregationType'] ?? $indicator->dessaggregationType,
                'description' => $sub['description'] ?? $indicator->description,
                'parent_indicator' => $indicator->id,
            ]);

            $subIndicator->save();

            $subProvinces = $sub["provinces"] ?? [];
            if (is_array($subProvinces) && count($subProvinces) > 0) {
                $subProvinceNames = collect($subProvinces)->pluck('province')->toArray();
                $subProvincesIds = Province::whereIn("name", $subProvinceNames)
                    ->pluck("id", "name")->toArray();

                $finalSubData = [];
                foreach ($subProvinces as $provinceData) {
                    $provinceName = strtolower($provinceData["province"]);
                    if (!isset($subProvincesIds[$provinceName])) continue;
                    $finalSubData[$subProvincesIds[$provinceName]] = [
                        "target" => $provinceData["target"],
                        "achived_target" => $provinceData["achived_target"] ?? 0,
                        "councilorCount" => $provinceData["councilorCount"] ?? 0,
                    ];
                }

                $subIndicator->provinces()->sync($finalSubData);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Indicator updated successfully.',
            'data' => [
                'id' => $indicator->id,
                'indicatorRef' => $indicator->indicatorRef
            ]
        ], 200);
    }


    // public function destroy(Request $request) {

    //     $ids = $request->input("ids");

    //     $request->validate([
    //         "ids" => "required|array",
    //         "ids.*" => "intager",
    //     ]);

    //     Indicator::whereIn("id", $ids)->delete();

    //     return response()->json(["status" => true, "message" => "Indicators successfully deleted !"], 200);

    // }


    public function destroy($id)
    {
        $indicator = Indicator::find($id);

        if (!$indicator) {
            return response()->json([
                'status' => false,
                'message' => 'Indicator not found.'
            ], 404);
        }

        $indicator->delete();

        return response()->json([
            'status' => true,
            'message' => 'Indicator successfully deleted!'
        ]);
    }

    public function setIsp3s (Request $request)
    {
        $isp3s = $request->input("isp3s");

        foreach ($isp3s as $isp3) {
            
            $dbIsp3 = Isp3::where("description", $isp3["isp3"])->first();

            $dbIsp3->indicators()->sync($isp3["indicators"]);

        }

        return response()->json(["status" => true, "message" => "Isp3's successfully saved !"], 200);
    }

}
