<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->string('day_basis', 10)->default('working')->after('color');
            $table->string('grant_mode', 10)->default('automatic')->after('day_basis');
            $table->boolean('is_statutory')->default(false)->after('grant_mode');
            $table->string('legal_basis', 120)->nullable()->after('is_statutory');
            $table->string('notes', 255)->nullable()->after('legal_basis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropColumn(['day_basis', 'grant_mode', 'is_statutory', 'legal_basis', 'notes']);
        });
    }
};
