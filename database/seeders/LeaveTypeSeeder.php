<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
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
                'color' => '#018002',
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
            [
                'name' => 'Service Incentive Leave',
                'code' => 'sil',
                'days_allowed' => 5,
                'is_paid' => true,
                'requires_doc' => false,
                'is_active' => true,
                'day_basis' => 'working',
                'grant_mode' => 'automatic',
                'is_statutory' => true,
                'legal_basis' => 'Labor Code Art. 95',
                'color' => '#018002',
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'days_allowed' => 105,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'day_basis' => 'calendar',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 11210',
                'color' => '#B8962A',
            ],
            [
                'name' => 'Maternity Leave (Miscarriage / Emergency Termination)',
                'code' => 'maternity_miscarriage',
                'days_allowed' => 60,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'day_basis' => 'calendar',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 11210',
                'color' => '#8A6F1D',
            ],
            [
                'name' => 'Maternity Leave Extension (Unpaid)',
                'code' => 'maternity_unpaid',
                'days_allowed' => 30,
                'is_paid' => false,
                'requires_doc' => false,
                'is_active' => true,
                'day_basis' => 'calendar',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 11210',
                'color' => '#7F8C8D',
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'paternity',
                'days_allowed' => 7,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'day_basis' => 'calendar',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 8187',
                'color' => '#1C7D13',
            ],
            [
                'name' => 'Solo Parent Leave',
                'code' => 'solo_parent',
                'days_allowed' => 7,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'day_basis' => 'working',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 8972 as amended by RA 11861',
                'color' => '#3B4F25',
            ],
            [
                'name' => 'VAWC Leave',
                'code' => 'vawc',
                'days_allowed' => 10,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'day_basis' => 'working',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 9262',
                'color' => '#0A4A12',
            ],
            [
                'name' => 'Special Leave for Women',
                'code' => 'women_special',
                'days_allowed' => 60,
                'is_paid' => true,
                'requires_doc' => true,
                'is_active' => true,
                'day_basis' => 'calendar',
                'grant_mode' => 'admin',
                'is_statutory' => true,
                'legal_basis' => 'RA 9710',
                'color' => '#67BD77',
            ],
        ];

        foreach ($types as $type) {
            LeaveType::firstOrCreate(['code' => $type['code']], $type);
        }

        app(LeaveBalanceService::class)->ensureActiveBalances();
    }
}
