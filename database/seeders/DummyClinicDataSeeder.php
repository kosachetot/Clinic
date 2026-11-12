<?php

namespace Database\Seeders;

use App\Models\AnnualMedicalExam;
use App\Models\PhilpenAssessment;
use App\Support\Hris;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * FAKE DATA ONLY, for testing the dashboard.
 * emp_id values start at 900001 so they can never match a real employee.
 */
class DummyClinicDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        mt_srand(2025);
        $year = 2025;

        // 128 fake employees spread over the real HRIS divisions
        $divisionIds = DB::connection('hris')->table('divisions')->orderBy('id')->pluck('id')->all();

        foreach (range(0, 127) as $i) {
            $divisionId = $divisionIds[$i % count($divisionIds)];
            $division = Hris::divisionCodeById($divisionId);
            $empId = 900001 + $i;
            $name = sprintf('Test Employee %03d', $i + 1);
            $sex = $this->chance(30) ? 'male' : 'female';
            $age = mt_rand(24, 64);

            // About 85% of the fake employees have a PhilPEN assessment
            if ($this->chance(85)) {
                PhilpenAssessment::firstOrCreate(
                    ['emp_id' => $empId, 'year' => $year],
                    [
                        'employee_name' => $name,
                        'designation' => 'Test Position',
                        'division_id' => $divisionId,
                        'division' => $division,
                        'assessed_at' => $this->randomDate($year),
                        'sex' => $sex,
                        'age' => $age,
                        'hx_ha_stroke_ckd' => $this->chance(10),
                        'family_history' => $this->chance(85),
                        'hypertension' => $this->weighted(['yes' => 20, 'meds' => 10, 'no' => 65, 'idk' => 5]),
                        'diabetes' => $this->weighted(['yes' => 8, 'meds' => 7, 'no' => 80, 'idk' => 5]),
                        'high_cholesterol' => $this->weighted(['yes' => 25, 'meds' => 10, 'no' => 55, 'idk' => 10]),
                        'tobacco_use' => $this->weighted(['current' => 10, 'former' => 20, 'never' => 70]),
                        'second_hand_smoke' => $this->chance(14),
                        'alcohol_intake' => $this->weighted(['heavy' => 21, 'occasional' => 45, 'none' => 34]),
                        'physically_active' => $this->chance(37),
                        'fruit_veg_intake' => $this->chance(60),
                        'high_fat_salt_sugar' => $this->chance(75),
                        'bmi_status' => $this->weighted(['underweight' => 5, 'normal' => 40, 'overweight' => 30, 'obese' => 25]),
                        'waist_cm' => mt_rand(600, 1100) / 10,
                        'srq_score' => mt_rand(0, 20),
                        // Random on purpose: the real cutoff is not confirmed yet
                        'needs_counselling' => $this->chance(15),
                        'burnout_category' => $this->weighted(['fine' => 70, 'be_careful' => 15, 'candidate' => 14, 'burning_out' => 1]),
                        'encoded_by' => 'seeder',
                    ]
                );
            }

            // About 65% have an annual medical exam
            if ($this->chance(65)) {
                $hasDiabetes = $this->chance(23);
                $highCholesterol = $this->chance(60);

                AnnualMedicalExam::firstOrCreate(
                    ['emp_id' => $empId, 'year' => $year],
                    [
                        'employee_name' => $name,
                        'designation' => 'Test Position',
                        'division_id' => $divisionId,
                        'division' => $division,
                        'sex' => $sex,
                        'age' => $age,
                        'exam_date' => $this->randomDate($year),
                        'glucose' => mt_rand(40, 75) / 10,
                        'hba1c' => $hasDiabetes ? mt_rand(66, 90) / 10 : mt_rand(42, 64) / 10,
                        'cholesterol' => $highCholesterol ? mt_rand(52, 70) / 10 : mt_rand(35, 51) / 10,
                        'triglyceride' => mt_rand(8, 25) / 10,
                        'hdl' => mt_rand(9, 22) / 10,
                        'ldl' => mt_rand(15, 40) / 10,
                        'xray_normal' => $this->chance(85),
                        'encoded_by' => 'seeder',
                    ]
                );
            }
        }
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    private function weighted(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    private function randomDate(int $year): string
    {
        return sprintf('%d-%02d-%02d', $year, mt_rand(3, 10), mt_rand(1, 28));
    }
}
