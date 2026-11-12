<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only access to the HRIS (hris connection): regular employees and divisions.
 */
class Hris
{
    // hr_infos.emp_status: 1 = regular employee, 0 = COS
    public const REGULAR = 1;

    /**
     * One row per regular employee: the latest hr_infos record,
     * joined with personal details, position and division.
     */
    public static function regularEmployees(): Builder
    {
        return DB::connection('hris')->table('hr_infos as i')
            ->join('hris_main as m', 'm.id', '=', 'i.emp_id')
            ->leftJoin('positions as p', 'p.id', '=', 'i.position_id')
            ->leftJoin('divisions as d', 'd.id', '=', 'i.division_id')
            ->where('i.emp_status', self::REGULAR)
            ->whereRaw('i.id = (select max(i2.id) from hr_infos as i2 where i2.emp_id = i.emp_id)');
    }

    public static function regularCount(): int
    {
        return self::regularEmployees()->count();
    }

    /** All HRIS divisions as [id, code, name] */
    public static function divisions(): Collection
    {
        return DB::connection('hris')->table('divisions')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => self::divisionCode($row->office),
                'name' => $row->description,
            ]);
    }

    /** Divisions that have regular employees, with the headcount of each */
    public static function divisionsWithHeadcount(): array
    {
        $counts = self::regularEmployees()
            ->select('i.division_id', DB::raw('count(*) as total'))
            ->groupBy('i.division_id')
            ->pluck('total', 'division_id');

        return self::divisions()
            ->map(fn ($division) => $division + ['count' => (int) ($counts[$division['id']] ?? 0)])
            ->filter(fn ($division) => $division['count'] > 0)
            ->values()
            ->all();
    }

    public static function divisionCode(?string $office): ?string
    {
        if (! $office) {
            return null;
        }

        return config('clinic.division_aliases', [])[$office] ?? $office;
    }

    public static function divisionCodeById(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        return self::divisionCode(
            DB::connection('hris')->table('divisions')->where('id', $id)->value('office')
        );
    }
}
