<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Starter departments only. No users or credentials are seeded.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'IT', 'code' => 'IT'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Operations', 'code' => 'OPS'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['name' => $department['name']],
                ['code' => $department['code'], 'is_active' => true]
            );
        }
    }
}
