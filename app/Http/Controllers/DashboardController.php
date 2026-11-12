<?php

namespace App\Http\Controllers;

use App\Models\AnnualMedicalExam;
use App\Models\PhilpenAssessment;
use App\Support\Hris;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $years = PhilpenAssessment::query()->pluck('year')
            ->merge(AnnualMedicalExam::query()->pluck('year'))
            ->unique()
            ->sortDesc()
            ->values();

        $year = (int) $request->input('year', $years->first() ?? now()->year);

        $philpen = PhilpenAssessment::where('year', $year)->get();
        $ame = AnnualMedicalExam::where('year', $year)->get();

        $divisions = Hris::divisionsWithHeadcount();
        $totalRegular = Hris::regularCount();

        return response()->json([
            'year' => $year,
            'available_years' => $years,
            'summary' => [
                'total_regular' => $totalRegular,
                'divisions' => $divisions,
            ],
            'philpen' => $this->philpenStats($philpen, $divisions),
            'ame' => $this->ameStats($ame, $divisions, $totalRegular),
            'completion' => $this->completionStats($philpen, $ame),
        ]);
    }

    private function philpenStats(Collection $rows, array $divisions): array
    {
        $count = fn (callable $test): int => $rows->filter($test)->count();

        $coverage = [];
        foreach ($divisions as $division) {
            $assessed = $rows->where('division_id', $division['id'])->count();

            $coverage[$division['code']] = [
                'assessed' => $assessed,
                'not_assessed' => max($division['count'] - $assessed, 0),
            ];
        }

        // "Meds" means the person is on medication, so it counts as having the condition
        $withHypertension = $rows->filter(
            fn ($r) => in_array($r->hypertension, ['yes', 'meds'], true)
        );

        return [
            'total' => $rows->count(),
            'coverage_by_division' => $coverage,
            'family_history' => [
                'yes' => $count(fn ($r) => $r->family_history === true),
                'no' => $count(fn ($r) => $r->family_history === false),
            ],
            'tobacco' => [
                'current' => $count(fn ($r) => $r->tobacco_use === 'current'),
                'former_or_never' => $count(fn ($r) => in_array($r->tobacco_use, ['former', 'never'], true)),
                'second_hand' => $count(fn ($r) => $r->second_hand_smoke === true),
            ],
            'alcohol' => [
                'heavy' => $count(fn ($r) => $r->alcohol_intake === 'heavy'),
                'occasional_or_none' => $count(fn ($r) => in_array($r->alcohol_intake, ['occasional', 'none'], true)),
            ],
            'physical_activity' => [
                'active' => $count(fn ($r) => $r->physically_active === true),
                'inactive' => $count(fn ($r) => $r->physically_active === false),
            ],
            'nutrition' => [
                'high_fat_salt_sugar' => $count(fn ($r) => $r->high_fat_salt_sugar === true),
                'no' => $count(fn ($r) => $r->high_fat_salt_sugar === false),
            ],
            'needs_counselling' => $count(fn ($r) => $r->needs_counselling === true),
            'burnout' => [
                'fine' => $count(fn ($r) => $r->burnout_category === 'fine'),
                'be_careful' => $count(fn ($r) => $r->burnout_category === 'be_careful'),
                'candidate' => $count(fn ($r) => $r->burnout_category === 'candidate'),
                'burning_out' => $count(fn ($r) => $r->burnout_category === 'burning_out'),
            ],
            'hypertension' => [
                'with' => $withHypertension->count(),
                'without' => $count(fn ($r) => $r->hypertension === 'no'),
                'by_age_group' => $this->byAgeGroup($withHypertension),
            ],
        ];
    }

    private function ameStats(Collection $rows, array $divisions, int $totalRegular): array
    {
        $lab = config('clinic.lab');

        $withHba1c = $rows->filter(fn ($r) => $r->hba1c !== null);
        $withCholesterol = $rows->filter(fn ($r) => $r->cholesterol !== null);

        $diabetes = $withHba1c->filter(fn ($r) => $r->hba1c > $lab['hba1c_max']);
        $highCholesterol = $withCholesterol->filter(fn ($r) => $r->cholesterol >= $lab['cholesterol_max']);

        $byDivision = [];
        foreach ($divisions as $division) {
            $with = $rows->where('division_id', $division['id'])->count();

            $byDivision[$division['code']] = [
                'with_ame' => $with,
                'without_ame' => max($division['count'] - $with, 0),
            ];
        }

        return [
            'total' => $rows->count(),
            'coverage' => [
                'with_ame' => $rows->count(),
                'without_ame' => max($totalRegular - $rows->count(), 0),
            ],
            'by_division' => $byDivision,
            'sex' => [
                'male' => $rows->where('sex', 'male')->count(),
                'female' => $rows->where('sex', 'female')->count(),
            ],
            'diabetes' => [
                'with' => $diabetes->count(),
                'normal' => $withHba1c->count() - $diabetes->count(),
                'by_age_group' => $this->byAgeGroup($diabetes),
            ],
            'cholesterol' => [
                'high' => $highCholesterol->count(),
                'normal' => $withCholesterol->count() - $highCholesterol->count(),
                'by_age_group' => $this->byAgeGroup($highCholesterol),
            ],
            // Number of employees with an abnormal result per test
            'lipid_profile' => [
                'cholesterol' => $highCholesterol->count(),
                'triglyceride' => $rows->filter(
                    fn ($r) => $r->triglyceride !== null && $r->triglyceride >= $lab['triglyceride_max']
                )->count(),
                'hdl' => $rows->filter(
                    fn ($r) => $r->hdl !== null && $r->hdl <= $lab['hdl_min']
                )->count(),
                'ldl' => $rows->filter(
                    fn ($r) => $r->ldl !== null && $r->ldl >= $lab['ldl_max']
                )->count(),
            ],
        ];
    }

    private function completionStats(Collection $philpen, Collection $ame): array
    {
        $philpenIds = $philpen->pluck('emp_id')->unique();
        $ameIds = $ame->pluck('emp_id')->unique();
        $complete = $philpenIds->intersect($ameIds)->count();

        return [
            'complete' => $complete,
            'either_only' => $philpenIds->merge($ameIds)->unique()->count() - $complete,
        ];
    }

    private function byAgeGroup(Collection $rows): array
    {
        $result = [];

        foreach (config('clinic.age_groups') as $label => [$min, $max]) {
            $result[$label] = $rows->filter(fn ($r) => $r->age >= $min && $r->age <= $max)->count();
        }

        return $result;
    }
}
