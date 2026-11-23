<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'days_allowed' => 20,
                'is_paid' => true,
                'requires_doc' => false,
                'is_active' => true,
                'color' => '#0F8B8D',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'days_allowed' => 10,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'color' => '#E67E22',
            ],
            [
                'name' => 'Personal Leave',
                'code' => 'personal',
                'days_allowed' => 5,
                'is_paid' => true,
                'requires_doc' => false,
                'is_active' => true,
                'color' => '#3498DB',
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'unpaid',
                'days_allowed' => 30,
                'is_paid' => false,
                'requires_doc' => false,
                'is_active' => true,
                'color' => '#7F8C8D',
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
