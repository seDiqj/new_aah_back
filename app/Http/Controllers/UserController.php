<?php

namespace App\Http\Controllers;

use App\Constants\System;
use App\Generators\DtoGenerator;
use App\Http\Requests\DestroyItemsRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ValidateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request, DtoGenerator $dtoGenerator, UserService $service)
    {
        
        $dto = $dtoGenerator->generateIndexUserDto($request);

        $users = $service->getUsers($dto);

        if ($users == System::NO_RECORDS) {
            return response()->json([
                "status" => System::NO_RECORDS_STATUS,
                "message" => "No user was found!",
                "data" => []
            ], System::NO_RECORDS_STATUS_CODE);
        }

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $users
        ], 200);
    }

    // public function index(Request $request)
    // {
    //     $query = User::query()->with('updater');

    //     $query->when($request->filled('name'), fn($q) => $q->where('name', "like", "%" . $request->name , "%"));
    //     $query->when($request->filled('email'), fn($q) => $q->where('email', "like", "%" . $request->email . "%"));
    //     $query->when($request->filled('title'), fn($q) => $q->where('title', "like", "%" . $request->title . "%"));
    //     $query->when($request->filled('status'), fn($q) => $q->where('status', "like", "%" . $request->status . "%"));
    //     $query->when($request->filled('created_at'), fn($q) => $q->whereDate('created_at', "like", "%" . $request->created_at . "%"));

    //     $query->when($search = $request->input('search'), fn($q) =>
    //         $q->where('name', 'like', "%{$search}%")
    //     );

    //     $users = $query->paginate(10);

    //     if ($users->isEmpty()) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "No user was found!",
    //             "data" => []
    //         ], 200);
    //     }

    //     $users->getCollection()->transform(function ($user) {
    //         $user->updated_by = $user->updater?->name; 
    //         unset($user->updater); 
    //         return $user;
    //     });

    //     return response()->json([
    //         "status" => true,
    //         "message" => "",
    //         "data" => $users
    //     ], 200);
    // }

    public function store(ValidateUserRequest $request, DtoGenerator $dtoGenerator, UserService $service)
    {
        $dto = $dtoGenerator->generateCreateUserDto($request);

        $user = $service->createUser($dto);

        return response()->json([
            'status' => true,
            'message' => 'User Created Successfully!',
            'data' => $user,
        ]);
    }
    // public function store(ValidateUserRequest $request, DtoGenerator $dtoGenerator, UserService $service)
    // {

    //     $data = $request->validated();

    //     $data['password'] = Hash::make($data['password']);

    //     $data['department_id'] = 1;

    //     if ($request->hasFile('photo_path')) {
    //         $photoPath = $request->file('photo_path')->store('images', 'public');
    //         $data['photo_path'] = $photoPath;
    //     }

    //     $data['created_by'] = Auth::id();

    //     $user = User::create($data);

    //     if (isset($data['role'])) {
    //         $user->assignRole($data['role']);
    //     }

    //     if (isset($photoPath)) {
    //         $user['photo_path'] = asset('storage/' . $photoPath);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User Created Successfully!',
    //         'data' => $user,
    //     ]);
    // }

    public function show(string $id, UserService $service)
    {
        // User | string
        $user = $service->showUser($id);

        if ($user == System::SYSTEM_404) {
            return response()->json([
                "status" => false,
                "message" => "No such user in database!"
            ], 404);    
        }

        return response()->json([
            "status" => true,
            "message" => "User retrieved successfully",
            "data" => $user,
        ], 200);
    }
    // public function show(string $id, UserService $service)
    // {
    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "No such user in database!"
    //         ], 404);    
    //     }

    //     $photo = $user->photo_path ? asset('storage/' . $user->photo_path) : null;

    //     $roles = $user->getRoleNames();

    //     $permissions = $user->getAllPermissions()->map(function ($p) {
    //         return [
    //             'id' => $p->id,
    //             'name' => $p->name,
    //             'label' => $p->label,
    //             'group_name' => $p->group_name ?? null,
    //         ];
    //     });

    //     return response()->json([
    //         "status" => true,
    //         "message" => "User retrieved successfully",
    //         "data" => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'title' => $user->title ?? null,
    //             'email' => $user->email,
    //             'photo_path' => $photo,
    //             'status' => $user->status,
    //             'roles' => $roles,
    //             'permissions' => $permissions,
    //         ]
    //     ], 200);
    // }

    public function update(UpdateUserRequest $request, string $id, DtoGenerator $dtoGenerator, UserService $service)
    {

        $dto = $dtoGenerator->generateUpdateUserDto($request);

        $user = $service->updateUser($dto, $id);
        
        return response()->json([
            'status' => true,
            'message' => 'User Updated Successfully!',
            'data' => $user,
        ]);
    }
    // public function update(Request $request, $id)
    // {

    //     $user = User::findOrFail($id);

    //     $data = $request->validate([
    //         "name" => "required|string|min:3",
    //         "title" => "nullable|string",
    //         "email" => "nullable|email",
    //         "password" => "nullable|string|min:6",
    //         "status" => "nullable|in:active,deactive",
    //         "department" => "nullable|string",
    //         "role" => "nullable|string",
    //         "permissions" => "nullable|string",
    //         "photo_path" => "nullable|image|max:2048",
    //     ]);


    //     if (!empty($data['password'])) {
    //         $data['password'] = Hash::make($data['password']);
    //     } else {
    //         unset($data['password']);
    //     }

    //     if (!empty($data['department'])) {
    //         $department = Department::where('name', $data['department'])->first();
    //         if ($department) {
    //             $data['department_id'] = $department->id;
    //         }
    //         unset($data['department']);
    //     }

    //     if ($request->hasFile('photo_path')) {
    //         $photoPath = $request->file('photo_path')->store('images', 'public');
    //         $data['photo_path'] = $photoPath; 
    //     }

    //     $data['updated_by'] = Auth::id();

    //     $user->update($data);

    //     if (!empty($data['role'])) {
    //         $user->syncRoles([$data['role']]);
    //     }

    //     if ($request->has('permissions') && is_array($request->permissions)) {
    //         $permissions = Permission::whereIn('id', $request->permissions)
    //             ->pluck('name')
    //             ->toArray();

    //         $user->syncPermissions($permissions);
    //     }

    //     // برای خروجی: تبدیل مسیر نسبی به URL کامل (اختیاری)
    //     if (isset($photoPath)) {
    //         $user->photo_path = asset('storage/' . $photoPath);
    //     } elseif ($user->photo_path) {
    //         $user->photo_path = asset('storage/' . $user->photo_path);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User Updated Successfully!',
    //         'data' => $user,
    //     ]);
    // }

    public function destroy(DestroyItemsRequest $request, DtoGenerator $dtoGenerator)
    {
        
        $dto = $dtoGenerator->generateDestroyItemsDto($request);

        User::whereIn("id", $dto->ids)->delete();

        return response()->json(["status" => true, "message" => "Users successfully deleted !"], 200);
        
    }

    public function me (UserService $service)
    {
        $id = Auth::id();

        $user = $service->showUser($id);

        if ($user == System::SYSTEM_404)
            return response()->json(["status" => false, "message" => "No such user in system", "data" => []], 404);

        return response()->json([
            "status" => true,
            "message" => "User retrieved successfully",
            "data" => $user
        ], 200);
    }
    // public function me ()
    // {

    //     $id = Auth::id();

    //     $user = User::find($id);

    //     if (!$user) {
    //         return response()->json([
    //             "status" => false,
    //             "message" => "Authintication failed !"
    //         ], 404);    
    //     }

    //     $photo = $user->photo_path ? asset('storage/' . $user->photo_path) : null;

    //     $roles = $user->getRoleNames();
         
    //     $permissions = $user->getAllPermissions()->map(function ($p) {
    //         return [
    //             'id' => $p->id,
    //             'name' => $p->name,
    //             'label' => $p->label,
    //             'group_name' => $p->group_name ?? null,
    //         ];
    //     });

    //     return response()->json([
    //         "status" => true,
    //         "message" => "User retrieved successfully",
    //         "data" => [
    //             'id' => $user->id,
    //             'name' => $user->name,
    //             'title' => $user->title ?? null,
    //             'email' => $user->email,
    //             'photo_path' => $photo,
    //             'status' => $user->status,
    //             'roles' => $roles,
    //             'permissions' => $permissions,
    //         ]
    //     ], 200);
    // }

    public function changeUserPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($id);

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully!',
        ]);
    }

    public function getSystemAndUserPermissions(string $id, UserService $service) 
    {
        $systemPermissions = $service->getSystemPermissions(true);

        $userPermissions = $service->getUserPermissions($id);

        if ($userPermissions == System::SYSTEM_404) {
            return response()->json([
                "status" => false,
                "message" => "No such user in database!",
                "data" => []
            ], 404);    
        }

        return response()->json([
            "status" => true,
            "message" => "Permissions retrieved successfully",
            "data" => [
                'system_permissions' => $systemPermissions,
                'user_permissions' => $userPermissions,
            ]
        ], 200);
    }

    public function getAllRolesAndPermissions(UserService $service)
    {
        $systemPermissions = $service->getSystemPermissions(true);

        $systemRoles = $service->getSystemActiveRoles();

        $finalData = [
            "permissions" => $systemPermissions,
            "roles" => $systemRoles
        ];

        return response()->json([
            "status" => true,
            "message" => "",
            "data" => $finalData
        ], 200);
    }
    // public function getAllRolesAndPermissions()
    // {
    //     $systemPermissions = Permission::all()->map(function ($p) {
    //         return [
    //             'id' => $p->id,
    //             'name' => $p->name,
    //             'label' => $p->label,
    //             'group_name' => $p->group_name ?? null,
    //         ];
    //     });

    //     $groupedPermissions = $systemPermissions->groupBy('group_name')->map(function ($group) {
    //         return $group->values();
    //     });

    //     $systemRoles = Role::with("permissions")->where("status", "!=", "deactive")->get();

    //     $finalData = [
    //         "permissions" => $groupedPermissions,
    //         "roles" => $systemRoles
    //     ];

    //     return response()->json([
    //         "status" => true,
    //         "message" => "",
    //         "data" => $finalData
    //     ], 200);
    // }

}
