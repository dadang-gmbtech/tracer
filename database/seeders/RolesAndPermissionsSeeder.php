<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-users',
            'manage-master-data',
            'manage-questions',
            'fill-tracer',
            'view-dashboard',
            'export-data',
            'upload-report',
            // Super Admin only (see $roleMap) — not part of Admin Universitas'
            // otherwise-identical "everything" list.
            'manage-home-content',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roleMap = [
            'Super Admin' => $permissions,
            'Admin Universitas' => array_diff($permissions, ['manage-home-content']),
            'Admin Fakultas' => ['manage-users', 'fill-tracer', 'view-dashboard', 'export-data', 'upload-report'],
            'Admin Prodi' => ['manage-users', 'fill-tracer', 'view-dashboard', 'export-data', 'upload-report'],
            'Surveyor' => ['fill-tracer'],
            'Alumni' => ['fill-tracer'],
            'Pimpinan Universitas' => ['view-dashboard'],
            'Pimpinan Fakultas' => ['view-dashboard'],
        ];

        foreach ($roleMap as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
