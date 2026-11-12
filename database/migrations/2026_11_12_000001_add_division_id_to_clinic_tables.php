<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['philpen_assessments', 'annual_medical_exams'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            // HRIS division id (divisions.id), used to group the reports
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('division_id')->nullable()->after('designation')->index();
            });

            // "division" keeps the short code shown in the lists (HRIS office names can be long)
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('division', 50)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('division_id');
            });
        }
    }
};
