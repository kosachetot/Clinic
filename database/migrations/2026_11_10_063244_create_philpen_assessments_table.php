<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('philpen_assessments', function (Blueprint $table) {
            $table->id();

            // Employee (emp_id comes from the HRIS)
            $table->unsignedInteger('emp_id')->index();
            $table->string('employee_name');
            $table->string('designation')->nullable();
            $table->string('division', 10)->nullable(); // MSD, LHSD, RLED, PDOHO

            $table->date('assessed_at');
            $table->unsignedSmallInteger('year')->index();
            $table->enum('sex', ['male', 'female']);
            $table->unsignedTinyInteger('age');

            // History and risk factors
            $table->boolean('hx_ha_stroke_ckd')->nullable();
            $table->boolean('family_history')->nullable();
            $table->enum('hypertension', ['yes', 'no', 'idk', 'meds'])->nullable();
            $table->enum('diabetes', ['yes', 'no', 'idk', 'meds'])->nullable();
            $table->enum('high_cholesterol', ['yes', 'no', 'idk', 'meds'])->nullable();

            // Lifestyle
            $table->enum('tobacco_use', ['current', 'former', 'never'])->nullable();
            $table->boolean('second_hand_smoke')->nullable();
            $table->enum('alcohol_intake', ['heavy', 'occasional', 'none'])->nullable();
            $table->boolean('physically_active')->nullable();
            $table->boolean('fruit_veg_intake')->nullable();
            $table->boolean('high_fat_salt_sugar')->nullable();

            // Measurements
            $table->enum('bmi_status', ['underweight', 'normal', 'overweight', 'obese'])->nullable();
            $table->decimal('waist_cm', 5, 1)->nullable();

            // Mental health
            $table->unsignedTinyInteger('srq_score')->nullable();
            $table->boolean('needs_counselling')->nullable();
            $table->unsignedTinyInteger('burnout_score')->nullable();
            $table->enum('burnout_category', ['fine', 'be_careful', 'candidate', 'burning_out'])->nullable();

            $table->text('remarks')->nullable();
            $table->string('encoded_by', 100)->nullable();
            $table->timestamps();

            // One assessment per employee per year
            $table->unique(['emp_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('philpen_assessments');
    }
};
