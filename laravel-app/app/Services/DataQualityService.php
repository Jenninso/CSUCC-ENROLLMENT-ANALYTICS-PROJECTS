<?php

namespace App\Services;

use App\Models\EnrollmentRecord;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class DataQualityService
{
    public function report(): array
    {
        $issues = [];

        $negatives = EnrollmentRecord::query()->where('enrollment_count', '<', 0)->count();
        if ($negatives) {
            $issues[] = ['type' => 'negative_count', 'count' => $negatives, 'message' => "{$negatives} record(s) have negative enrollment counts."];
        }

        $missingProgram = EnrollmentRecord::query()->whereNull('program_id')->count();
        if ($missingProgram) {
            $issues[] = ['type' => 'missing_program', 'count' => $missingProgram, 'message' => "{$missingProgram} record(s) are missing a program."];
        }

        $invalidYear = EnrollmentRecord::query()->where(function ($q) {
            $q->where('year_level', '<', 1)->orWhere('year_level', '>', 6);
        })->count();
        if ($invalidYear) {
            $issues[] = ['type' => 'invalid_year_level', 'count' => $invalidYear, 'message' => "{$invalidYear} record(s) have an invalid year level."];
        }

        $invalidType = EnrollmentRecord::query()
            ->whereNotIn('enrollment_type', ['new', 'returning', 'continuing', 'transferee', 'graduating', 'withdrawn', 'dropped'])
            ->count();
        if ($invalidType) {
            $issues[] = ['type' => 'invalid_type', 'count' => $invalidType, 'message' => "{$invalidType} record(s) have an unrecognized enrollment type."];
        }

        $duplicateGroups = EnrollmentRecord::query()
            ->select('academic_year_id', 'semester_id', 'program_id', 'year_level', 'enrollment_type', DB::raw('COUNT(*) as c'))
            ->groupBy('academic_year_id', 'semester_id', 'program_id', 'year_level', 'enrollment_type')
            ->havingRaw('COUNT(*) > 1')
            ->get();
        if ($duplicateGroups->count()) {
            $issues[] = [
                'type' => 'duplicates',
                'count' => $duplicateGroups->count(),
                'message' => $duplicateGroups->count().' duplicate year/semester/program/level/type combinations.',
            ];
        }

        $orphanPrograms = Program::query()->doesntHave('enrollmentRecords')->count();
        if ($orphanPrograms) {
            $issues[] = ['type' => 'unused_program', 'count' => $orphanPrograms, 'message' => "{$orphanPrograms} program(s) have no enrollment records."];
        }

        $totalRecords = EnrollmentRecord::count();
        $issueCount = array_sum(array_column($issues, 'count'));
        $score = $totalRecords === 0
            ? 0
            : round(max(0, ($totalRecords - $issueCount) / $totalRecords) * 100, 1);

        return [
            'score' => $score,
            'issues' => $issues,
            'total_records' => $totalRecords,
            'issue_count' => $issueCount,
        ];
    }
}
