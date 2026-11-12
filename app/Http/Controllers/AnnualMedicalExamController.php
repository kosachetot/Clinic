<?php

namespace App\Http\Controllers;

use App\Models\AnnualMedicalExam;
use App\Support\Hris;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AnnualMedicalExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AnnualMedicalExam::query();

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
        $data['year'] = Carbon::parse($data['exam_date'])->year;
        $data['division'] = Hris::divisionCodeById($data['division_id'] ?? null);

        if ($this->duplicateExists($data['emp_id'], $data['year'])) {
            return $this->duplicateResponse();
        }

        return response()->json(AnnualMedicalExam::create($data), 201);
    }

    public function show(AnnualMedicalExam $annualMedicalExam): JsonResponse
    {
        return response()->json($annualMedicalExam);
    }

    public function update(Request $request, AnnualMedicalExam $annualMedicalExam): JsonResponse
    {
        $data = $this->validated($request);
        $data['year'] = Carbon::parse($data['exam_date'])->year;
        $data['division'] = Hris::divisionCodeById($data['division_id'] ?? null);

        if ($this->duplicateExists($data['emp_id'], $data['year'], $annualMedicalExam->id)) {
            return $this->duplicateResponse();
        }

        $annualMedicalExam->update($data);

        return response()->json($annualMedicalExam->fresh());
    }

    public function destroy(AnnualMedicalExam $annualMedicalExam): JsonResponse
    {
        $annualMedicalExam->delete();

        return response()->json(['message' => 'Annual medical exam deleted.']);
    }

    // Years that have at least one record (used by the Year filter)
    public function years(): JsonResponse
    {
        return response()->json(
            AnnualMedicalExam::query()->distinct()->orderByDesc('year')->pluck('year')
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'emp_id' => ['required', 'integer', 'min:1'],
            'employee_name' => ['required', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'integer', 'exists:hris.divisions,id'],
            'sex' => ['nullable', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'between:18,100'],
            'exam_date' => ['required', 'date'],
            'glucose' => ['nullable', 'numeric', 'between:0,60'],
            'hba1c' => ['nullable', 'numeric', 'between:0,25'],
            'cholesterol' => ['nullable', 'numeric', 'between:0,30'],
            'triglyceride' => ['nullable', 'numeric', 'between:0,50'],
            'hdl' => ['nullable', 'numeric', 'between:0,10'],
            'ldl' => ['nullable', 'numeric', 'between:0,20'],
            'xray_normal' => ['nullable', 'boolean'],
            'xray_others' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            // TODO: set encoded_by from the logged-in user once token login is added
            'encoded_by' => ['nullable', 'string', 'max:100'],
        ]);
    }

    private function duplicateExists(int $empId, int $year, ?int $ignoreId = null): bool
    {
        return AnnualMedicalExam::where('emp_id', $empId)
            ->where('year', $year)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function duplicateResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'This employee already has an annual medical exam for that year.',
        ], 422);
    }
}
