<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Starter positions only. No users or credentials are seeded.
     */
    public function run(): void
    {
        $positions = [
            'Human Resources' => ['HR Manager', 'HR Specialist', 'Recruiter'],
            'Marketing' => ['Marketing Manager', 'Marketing Specialist', 'Content Strategist'],
            'IT' => ['IT Manager', 'Software Developer', 'Support Engineer'],
            'Finance' => ['Finance Manager', 'Accountant', 'Payroll Specialist'],
            'Operations' => ['Operations Manager', 'Operations Coordinator', 'Logistics Specialist'],
        ];

        foreach ($positions as $departmentName => $names) {
            $department = Department::where('name', $departmentName)->firstOrFail();

            foreach ($names as $name) {
                Position::updateOrCreate(
                    ['department_id' => $department->id, 'name' => $name],
                    ['is_active' => true]
                );
            }
        }
    }
}
