<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AiInsight;
use App\Models\College;
use App\Models\Department;
use App\Models\EnrollmentRecord;
use App\Models\Forecast;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CeitBsitScopeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $college = College::query()->updateOrCreate(
                ['college_code' => config('scope.college_code')],
                ['college_name' => config('scope.college_name')]
            );

            // department_code is globally unique — reuse existing IT and attach to CEIT.
            $departments = [];
            foreach (config('scope.departments') as $code => $name) {
                $departments[$code] = Department::query()->updateOrCreate(
                    ['department_code' => $code],
                    [
                        'department_name' => $name,
                        'college_id' => $college->id,
                    ]
                );
            }

            $programIds = [];
            foreach (config('scope.programs') as $code => $program) {
                $saved = Program::query()->updateOrCreate(
                    ['program_code' => $code],
                    [
                        'program_name' => $program['name'],
                        'department_id' => $departments[$program['department']]->id,
                        'degree_level' => config('scope.degree_level'),
                        'status' => 'active',
                    ]
                );
                $programIds[] = $saved->id;
            }

            $it = $departments[config('scope.department_code')];
            $bsit = Program::query()->where('program_code', config('scope.program_code'))->first();
            if ($bsit) {
                EnrollmentRecord::query()
                    ->where('program_id', $bsit->id)
                    ->update([
                        'college_id' => $college->id,
                        'department_id' => $it->id,
                    ]);
            }

            Alert::query()->whereNotNull('program_id')->whereNotIn('program_id', $programIds)->delete();

            Forecast::query()->where(function ($q) use ($programIds, $college) {
                $q->where(function ($inner) use ($programIds) {
                    $inner->whereNotNull('program_id')->whereNotIn('program_id', $programIds);
                })->orWhere(function ($inner) use ($college) {
                    $inner->whereNotNull('college_id')->where('college_id', '!=', $college->id);
                });
            })->delete();

            AiInsight::query()->whereNotNull('program_id')->whereNotIn('program_id', $programIds)->delete();

            EnrollmentRecord::query()->whereNotIn('program_id', $programIds)->delete();
            EnrollmentRecord::query()->where('college_id', '!=', $college->id)->delete();

            Program::query()->whereNotIn('program_code', array_keys(config('scope.programs')))->delete();
            Department::query()->whereNotIn('department_code', array_keys(config('scope.departments')))->delete();
            College::query()->where('id', '!=', $college->id)->delete();
        });
    }
}
