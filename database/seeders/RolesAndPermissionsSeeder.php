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
            // Bulk Excel import of tracer/alumni master data — deliberately
            // separate from fill-tracer so an Alumni account (which needs
            // fill-tracer to fill in their own questionnaire) can't also
            // bulk-upload data for other alumni. Restricted to Super
            // Admin/Admin Universitas only (see $roleMap) — Admin Fakultas/
            // Admin Prodi/Surveyor no longer get it.
            'import-data',
            // Bulk Excel import of Pengguna Alumni (employer) feedback —
            // kept separate from import-data above because it stays open to
            // Admin Fakultas/Admin Prodi/Surveyor, unlike tracer/alumni import.
            'import-employer-data',
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
            'Admin Fakultas' => ['manage-users', 'fill-tracer', 'import-employer-data', 'view-dashboard', 'export-data', 'upload-report'],
            'Admin Prodi' => ['manage-users', 'fill-tracer', 'import-employer-data', 'view-dashboard', 'export-data', 'upload-report'],
            'Surveyor' => ['fill-tracer', 'import-employer-data'],
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
