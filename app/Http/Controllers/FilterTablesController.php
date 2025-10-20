<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class FilterTablesController extends Controller
{
    public function filterProjects(Request $request) 
    {
        $query = Project::query();

        if ($request->filled('projectCode')) {
            $query->where('projectCode', 'like', '%' . $request->projectCode . '%');
        }

        if ($request->filled('projectManager')) {
            $query->where('projectManager', $request->projectManager);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            return response()->json([
                "status" => false,
                "message" => "No project available in database records"
            ], 404);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $projects
        ]);
    }

}
