<?php

namespace App\Http\Controllers;

use App\Models\PhilpenAssessment;
use App\Support\Hris;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PhilpenAssessmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PhilpenAssessment::query();

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        if ($request->filled('division_id')) {
            $query->where('division_id', (int) $request->input('division_id'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->input('search').'%';

            $query->where(function ($q) use ($term) {
                $q->where('employee_name', 'like', $term)
                    ->orWhere('emp_id', 'like', $term)
                    ->orWhere('designation', 'like', $term);
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        return response()->json($query->orderBy('employee_name')->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['year'] = Carbon::parse($data['assessed_at'])->year;
        $data['division'] = Hris::divisionCodeById($data['division_id'] ?? null);

        if ($this->duplicateExists($data['emp_id'], $data['year'])) {
            return $this->duplicateResponse();
        }

        return response()->json(PhilpenAssessment::create($data), 201);
    }

    public function show(PhilpenAssessment $philpenAssessment): JsonResponse
    {
        return response()->json($philpenAssessment);
    }

    public function update(Request $request, PhilpenAssessment $philpenAssessment): JsonResponse
    {
        $data = $this->validated($request);
        $data['year'] = Carbon::parse($data['assessed_at'])->year;
        $data['division'] = Hris::divisionCodeById($data['division_id'] ?? null);

        if ($this->duplicateExists($data['emp_id'], $data['year'], $philpenAssessment->id)) {
            return $this->duplicateResponse();
        }

        $philpenAssessment->update($data);

        return response()->json($philpenAssessment->fresh());
    }

    public function destroy(PhilpenAssessment $philpenAssessment): JsonResponse
    {
        $philpenAssessment->delete();

        return response()->json(['message' => 'Assessment deleted.']);
    }

    // Years that have at least one record (used by the Year filter)
    public function years(): JsonResponse
    {
        return response()->json(
            PhilpenAssessment::query()->distinct()->orderByDesc('year')->pluck('year')
        );
    }

    private function validated(Request $request): array
    {
        $yesNo = ['nullable', 'boolean'];
        $answer = ['nullable', Rule::in(['yes', 'no', 'idk', 'meds'])];

        return $request->validate([
            'emp_id' => ['required', 'integer', 'min:1'],
            'employee_name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'integer', 'exists:hris.divisions,id'],
            'assessed_at' => ['required', 'date'],
            'sex' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'between:18,100'],
            'hx_ha_stroke_ckd' => $yesNo,
            'family_history' => $yesNo,
            'hypertension' => $answer,
            'diabetes' => $answer,
            'high_cholesterol' => $answer,
            'tobacco_use' => ['nullable', Rule::in(['current', 'former', 'never'])],
            'second_hand_smoke' => $yesNo,
            'alcohol_intake' => ['nullable', Rule::in(['heavy', 'occasional', 'none'])],
            'physically_active' => $yesNo,
            'fruit_veg_intake' => $yesNo,
            'high_fat_salt_sugar' => $yesNo,
            'bmi_status' => ['nullable', Rule::in(['underweight', 'normal', 'overweight', 'obese'])],
            'waist_cm' => ['nullable', 'numeric', 'between:30,250'],
            'srq_score' => ['nullable', 'integer', 'between:0,30'],
            'needs_counselling' => $yesNo,
            'burnout_score' => ['nullable', 'integer', 'between:0,200'],
            'burnout_category' => ['nullable', Rule::in(['fine', 'be_careful', 'candidate', 'burning_out'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
            // TODO: set encoded_by from the logged-in user once token login is added
            'encoded_by' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function duplicateExists(int $empId, int $year, ?int $ignoreId = null): bool
    {
        return PhilpenAssessment::where('emp_id', $empId)
            ->where('year', $year)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function duplicateResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'This employee already has an assessment for that year.',
        ], 422);
    }
}
