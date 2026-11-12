<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_medical_exams', function (Blueprint $table) {
            $table->id();

            // Employee (emp_id comes from the HRIS)
            $table->unsignedInteger('emp_id')->index();
            $table->string('employee_name');
            $table->string('designation')->nullable();
            $table->string('division', 10)->nullable();
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->unsignedTinyInteger('age');

            $table->date('exam_date');
            $table->unsignedSmallInteger('year')->index();

            // Laboratory results (SI units, reference range in comments)
            $table->decimal('glucose', 5, 2)->nullable();      // mmol/L (4.1 - 5.9)
            $table->decimal('hba1c', 4, 2)->nullable();        // % (4.0 - 6.5)
            $table->decimal('cholesterol', 5, 2)->nullable();  // mmol/L (< 5.2)
            $table->decimal('triglyceride', 5, 2)->nullable(); // mmol/L (< 1.7)
            $table->decimal('hdl', 5, 2)->nullable();          // mmol/L (> 1.55)
            $table->decimal('ldl', 5, 2)->nullable();          // mmol/L (< 2.6)

            $table->boolean('xray_normal')->nullable();
            $table->string('xray_others')->nullable();
            $table->text('remarks')->nullable();
            $table->string('encoded_by', 100)->nullable();
            $table->timestamps();

            // One exam record per employee per year
            $table->unique(['emp_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_medical_exams');
    }
};
