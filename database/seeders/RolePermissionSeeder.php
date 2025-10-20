<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ===================== Roles =====================
        $roles = [
            'Sys_admin',
            'CD/DCD',
            'HoD/DHoD/FM',
            'Grant Co',
            'HQ',
            'RFM',
            'PM/DPM',
            'Supervisor',
            'Data Entry',
            'User',
        ];

        // ===================== Create all permissions =====================
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                "label" => strtoupper($permission->value),
                "group_name" => "group"
            ]);
        }

        // ===================== Assign permissions =====================
        $rolePermissions = [

            'Sys_admin' => array_map(fn($p) => $p->value, PermissionEnum::cases()),

            'CD/DCD' => [
                PermissionEnum::USER_VIEW->value,
                PermissionEnum::ROLE_VIEW->value,
                PermissionEnum::GRANT_PROJECT_VIEW->value,
                PermissionEnum::APR_VIEW->value,
                PermissionEnum::DASHBOARD_MONTH_REPORT_CHART->value,
                PermissionEnum::DASHBOARD_PROJECT_ACTIVITIES->value,
            ],

            'HoD/DHoD/FM' => [
                PermissionEnum::USER_VIEW->value,
                PermissionEnum::GRANT_PROJECT_CREATE->value,
                PermissionEnum::GRANT_PROJECT_EDIT->value,
                PermissionEnum::GRANT_PROJECT_SUBMIT->value,
                PermissionEnum::APR_REVIEW->value,
                PermissionEnum::APR_VIEW->value,
            ],

            'Grant Co' => [
                PermissionEnum::GRANT_PROJECT_CREATE->value,
                PermissionEnum::GRANT_PROJECT_EDIT->value,
                PermissionEnum::GRANT_PROJECT_DELETE->value,
                PermissionEnum::GRANT_PROJECT_VIEW->value,
                PermissionEnum::GRANT_PROJECT_SUBMIT->value,
            ],

            'HQ' => [
                PermissionEnum::GRANT_PROJECT_HQ_FINALIZE->value,
                PermissionEnum::DATABASE_APPROVE->value,
                PermissionEnum::APR_VALIDATE->value,
                PermissionEnum::APR_DOWNLOAD->value,
            ],

            'RFM' => [
                PermissionEnum::KIT_VIEW->value,
                PermissionEnum::PSYCHO_VIEW->value,
                PermissionEnum::DIALOGUE_VIEW->value,
                PermissionEnum::TRAINING_VIEW->value ?? null,
                PermissionEnum::REFERRAL_VIEW->value,
                PermissionEnum::DATABASE_VIEW->value,
            ],

            // --- PM/DPM ---
            'PM/DPM' => [
                PermissionEnum::GRANT_PROJECT_VIEW->value,
                PermissionEnum::DIALOGUE_CREATE->value,
                PermissionEnum::DIALOGUE_EDIT->value,
                PermissionEnum::DIALOGUE_DELETE->value,
                PermissionEnum::DIALOGUE_ASSIGN->value,
                PermissionEnum::PSYCHO_CREATE->value,
                PermissionEnum::PSYCHO_EDIT->value,
                PermissionEnum::PSYCHO_DELETE->value,
            ],

            // --- Supervisor ---
            'Supervisor' => [
                PermissionEnum::TRAINING_CREATE->value,
                PermissionEnum::TRAINING_EDIT->value,
                PermissionEnum::TRAINING_DELETE->value,
                PermissionEnum::REFERRAL_CREATE->value,
                PermissionEnum::REFERRAL_EDIT->value,
                PermissionEnum::REFERRAL_DELETE->value,
                // PermissionEnum::DATABASE_SUBMISSION_CREATE->value ?? PermissionEnum::DATABASE_CREATE->value,
            ],

            // --- Data Entry ---
            'Data Entry' => [
                PermissionEnum::MHPSS_CREATE->value,
                PermissionEnum::MHPSS_EDIT->value,
                PermissionEnum::MHPSS_DELETE->value,
                PermissionEnum::KIT_CREATE->value,
                PermissionEnum::KIT_EDIT->value,
                PermissionEnum::KIT_DELETE->value,
                PermissionEnum::REFERRAL_CREATE->value,
                PermissionEnum::REFERRAL_EDIT->value,
            ],

            // --- User ---
            'User' => [
                PermissionEnum::USER_VIEW->value,
                PermissionEnum::ROLE_VIEW->value,
                PermissionEnum::DASHBOARD_FILTERING->value,
            ],
        ];

        // ===================== Apply roles and permissions =====================
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $permissions = $rolePermissions[$roleName] ?? [];

            $permissions = array_filter($permissions);  
            $role->syncPermissions($permissions);
        }
    }
}