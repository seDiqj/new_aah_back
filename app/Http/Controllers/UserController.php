<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidateUserRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    public function index()
    {

        $users = User::orderBy("created_at", "desc")->get();

        if (!$users) return response()->json(["status" => false, "message" => "Somthing gone wronge !"], 401);

        return response()->json(["status" => true, "message" => "", "data" => $users], 200);
        
    }

    public function store(ValidateUserRequest $request)
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        $department = Department::where('name', $data['department'])->first();
        if (!$department) {
            return response()->json([
                'status' => false,
                'message' => 'Department not found'
            ], 404);
        }
        $data['department_id'] = $department->id;

        if ($request->hasFile('photo_path')) {
            $photoPath = $request->file('photo_path')->store('images', 'public');
            $data['photo_path'] = $photoPath;
        }

        $data['created_by'] = Auth::id();

        $user = User::create($data);

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        if (isset($photoPath)) {
            $user['photo_path'] = asset('storage/' . $photoPath);
        }

        return response()->json([
            'status' => true,
            'message' => 'User Created Successfully!',
            'data' => $user,
        ]);
    }


    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => "No such user in database!"
            ], 404);    
        }

        $photo = $user->photo_path ? asset('storage/' . $user->photo_path) : null;

        $roles = $user->getRoleNames();

        $permissions = $user->getAllPermissions()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->label,
                'group_name' => $p->group_name ?? null,
            ];
        });

        return response()->json([
            "status" => true,
            "message" => "User retrieved successfully",
            "data" => [
                'id' => $user->id,
                'name' => $user->name,
                'title' => $user->title ?? null,
                'email' => $user->email,
                'photo_path' => $photo,
                'status' => $user->status,
                'roles' => $roles,
                'permissions' => $permissions,
            ]
        ], 200);
    }



    public function update(Request $request, $id)
    {

        $user = User::findOrFail($id);

        $data = $request->validate([
            "name" => "required|string|min:3",
            "title" => "nullable|string",
            "email" => "nullable|email",
            "status" => "nullable|in:active,inactive",
            "department" => "nullable|string",
            "role" => "nullable|string",
            "photo_path" => "nullable|image|max:2048",
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (!empty($data['department'])) {
            $department = Department::where('name', $data['department'])->first();
            if ($department) {
                $data['department_id'] = $department->id;
            }
            unset($data['department']);
        }

        if ($request->hasFile('photo_path')) {
            $photoPath = $request->file('photo_path')->store('images', 'public');
            $data['photo_path'] = $photoPath; 
        }

        $data['updated_by'] = Auth::id();

        $user->update($data);

        if (!empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        if ($request->has('permissions') && is_array($request->permissions)) {
            $permissions = Permission::whereIn('id', $request->permissions)
                ->pluck('name')
                ->toArray();

            $user->syncPermissions($permissions);
        }

        // برای خروجی: تبدیل مسیر نسبی به URL کامل (اختیاری)
        if (isset($photoPath)) {
            $user->photo_path = asset('storage/' . $photoPath);
        } elseif ($user->photo_path) {
            $user->photo_path = asset('storage/' . $user->photo_path);
        }

        return response()->json([
            'status' => true,
            'message' => 'User Updated Successfully!',
            'data' => $user,
        ]);
    }


    public function destroy(Request $request)
    {

        $ids = $request->input("ids");

        $request->validate([
            "ids" => "required|array",
            "ids.*" => "integer"
        ]);

        User::whereIn("id", $ids)->delete();

        return response()->json(["status" => true, "message" => "Users successfully deleted !"], 200);
        
    }

    public function me ()
    {

        $id = Auth::id();

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => "Authintication failed !"
            ], 404);    
        }

        $photo = $user->photo_path ? asset('storage/' . $user->photo_path) : null;

        $roles = $user->getRoleNames();
         
        $permissions = $user->getAllPermissions()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->label,
                'group_name' => $p->group_name ?? null,
            ];
        });

        return response()->json([
            "status" => true,
            "message" => "User retrieved successfully",
            "data" => [
                'id' => $user->id,
                'name' => $user->name,
                'title' => $user->title ?? null,
                'email' => $user->email,
                'photo_path' => $photo,
                'status' => $user->status,
                'roles' => $roles,
                'permissions' => $permissions,
            ]
        ], 200);
    }
}
