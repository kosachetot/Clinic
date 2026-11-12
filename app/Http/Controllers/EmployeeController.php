<?php

namespace App\Http\Controllers;

use App\Support\Hris;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Read-only lookup of regular employees from the HRIS (hris connection).
 * Only the fields needed to fill in the forms are returned.
 */
class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->input('limit', 25), 1), 50);

        $query = Hris::regularEmployees()->select([
            'i.emp_id',
            'i.division_id',
            'd.office as division_office',
            'd.description as division_name',
            'm.sur_name',
            'm.first_name',
            'm.middle_name',
            'm.suffix_name',
            'm.sex',
            'm.date_birth',
            'p.position',
        ]);

        // Every typed word must match the surname, first name, middle name, or employee no.
        $words = preg_split('/[\s,]+/', trim((string) $request->input('search', '')), -1, PREG_SPLIT_NO_EMPTY);

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '\\%_').'%';

            $query->where(function ($q) use ($like, $word) {
                $q->where('m.sur_name', 'like', $like)
                    ->orWhere('m.first_name', 'like', $like)
                    ->orWhere('m.middle_name', 'like', $like);

                if (ctype_digit($word)) {
                    $q->orWhere('i.emp_id', (int) $word);
                }
            });
        }

        $employees = $query
            ->orderBy('m.sur_name')
            ->orderBy('m.first_name')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->toArray($row))
            ->values();

        return response()->json($employees);
    }

    private function toArray(object $row): array
    {
        return [
            'emp_id' => (int) $row->emp_id,
            'name' => $this->formatName($row),
            'designation' => $row->position ?: null,
            'division_id' => $row->division_id ? (int) $row->division_id : null,
            'division' => Hris::divisionCode($row->division_office),
            'division_name' => $row->division_name,
            // HRIS stores sex as 1 = male, 2 = female
            'sex' => [1 => 'male', 2 => 'female'][(int) $row->sex] ?? null,
            'age' => $this->ageOf($row->date_birth),
        ];
    }

    private function formatName(object $row): string
    {
        $name = trim((string) $row->sur_name).', '.trim((string) $row->first_name);

        $middle = trim((string) $row->middle_name);
        if ($middle !== '' && ! in_array(strtoupper($middle), ['N/A', 'NA', 'NONE'], true)) {
            $name .= ' '.mb_substr($middle, 0, 1).'.';
        }

        $suffix = trim((string) $row->suffix_name);
        if ($suffix !== '' && ! in_array(strtoupper($suffix), ['N/A', 'NA', 'NONE'], true)) {
            $name .= ' '.$suffix;
        }

        return $name;
    }

    // Only the age is sent to the frontend, never the birth date
    private function ageOf(?string $birthDate): ?int
    {
        if (! $birthDate || str_starts_with($birthDate, '0000')) {
            return null;
        }

        try {
            $age = Carbon::parse($birthDate)->age;
        } catch (\Throwable) {
            return null;
        }

        return ($age >= 15 && $age <= 100) ? $age : null;
    }
}
