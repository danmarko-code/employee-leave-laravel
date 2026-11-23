<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Roles are fixed: only Employee and Administrator exist.
     */
    public function run(): void
    {
        $employee = Role::updateOrCreate(
            ['name' => 'employee'],
            [
                'display_name' => 'Employee',
                'description' => 'Applies for leave, tracks balances and manages their own profile.',
            ]
        );

        $admin = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Manages employees, approvals, organization settings and reports.',
            ]
        );

        $fixedRoleIds = [$employee->id, $admin->id];

        User::whereNotIn('role_id', $fixedRoleIds)->update(['role_id' => $employee->id]);
        Role::whereNotIn('id', $fixedRoleIds)->delete();
    }
}
