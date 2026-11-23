<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Move old Approver users to Employee and remove manager/head columns.
     */
    public function up(): void
    {
        $employeeId = DB::table('roles')->where('name', 'employee')->value('id');
        $adminId = DB::table('roles')->where('name', 'admin')->value('id');

        if ($employeeId) {
            $fixedRoleIds = array_filter([$employeeId, $adminId]);

            // Approver users, and any other legacy role users, become employees.
            DB::table('users')->whereNotIn('role_id', $fixedRoleIds)->update(['role_id' => $employeeId]);
            DB::table('roles')->whereNotIn('id', $fixedRoleIds)->delete();
        }

        if (Schema::hasColumn('users', 'manager_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['manager_id']);
                $table->dropIndex(['manager_id']);
                $table->dropColumn('manager_id');
            });
        }

        if (Schema::hasColumn('departments', 'head_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropForeign(['head_id']);
                $table->dropColumn('head_id');
            });
        }
    }

    /**
     * Restore the old columns and role for local rollbacks.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'manager_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('manager_id')->nullable()->after('position_id')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('departments', 'head_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('head_id')->nullable()->after('code');
            });

            Schema::table('departments', function (Blueprint $table) {
                $table->foreign('head_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        DB::table('roles')->updateOrInsert(
            ['name' => 'approver'],
            ['display_name' => 'Approver', 'description' => 'Legacy approval role.', 'created_at' => now(), 'updated_at' => now()]
        );
    }
};
