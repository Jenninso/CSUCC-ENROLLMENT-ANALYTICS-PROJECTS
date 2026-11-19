<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SubjectCurriculumService
{
    /**
     * Standard CEIT Curriculums by Program, Year Level, and Semester.
     * Each subject includes code, title, units, category, and historical difficulty rating (0.0 to 1.0).
     */
    private const CURRICULUM = [
        'BSIT' => [
            1 => [
                1 => [
                    ['code' => 'CC 101', 'title' => 'Introduction to Computing', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.35],
                    ['code' => 'CC 102', 'title' => 'Fundamentals of Programming', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.75],
                    ['code' => 'MATH 101', 'title' => 'Mathematics in the Modern World', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.55],
                    ['code' => 'GE 101', 'title' => 'Understanding the Self', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'GE 102', 'title' => 'Purposive Communication', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'FIL 1', 'title' => 'Kontekstwalisadong Komunikasyon', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'PE 1', 'title' => 'Physical Fitness and Wellness', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                    ['code' => 'NSTP 1', 'title' => 'National Service Training Program 1', 'units' => 3.0, 'category' => 'NSTP', 'difficulty' => 0.10],
                ],
                2 => [
                    ['code' => 'CC 103', 'title' => 'Intermediate Computer Programming', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.82],
                    ['code' => 'IT 101', 'title' => 'Discrete Mathematics for IT', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.78],
                    ['code' => 'GE 104', 'title' => 'Readings in Philippine History', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'GE 105', 'title' => 'The Contemporary World', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.18],
                    ['code' => 'GE 106', 'title' => 'Art Appreciation', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'FIL 2', 'title' => 'Filipino sa Iba\'t Ibang Disiplina', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'PE 2', 'title' => 'Rhythmic Activities', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                    ['code' => 'NSTP 2', 'title' => 'National Service Training Program 2', 'units' => 3.0, 'category' => 'NSTP', 'difficulty' => 0.10],
                ],
            ],
            2 => [
                1 => [
                    ['code' => 'CC 104', 'title' => 'Data Structures and Algorithms', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.90],
                    ['code' => 'IT 201', 'title' => 'Object-Oriented Programming', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.80],
                    ['code' => 'IT 202', 'title' => 'Platform Technologies & OS', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.50],
                    ['code' => 'IT 203', 'title' => 'Quantitative Methods & Modeling', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.70],
                    ['code' => 'GE 107', 'title' => 'Science, Technology, and Society', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'GE 108', 'title' => 'Ethics', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'ENGL 1', 'title' => 'Technical Writing in Computing', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.30],
                    ['code' => 'PE 3', 'title' => 'Individual and Dual Sports', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                ],
                2 => [
                    ['code' => 'IT 204', 'title' => 'Information Management (Database 1)', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.74],
                    ['code' => 'IT 205', 'title' => 'Web Systems and Technologies 1', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.72],
                    ['code' => 'IT 206', 'title' => 'Networking 1 (Routing & Switching)', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.76],
                    ['code' => 'IT 207', 'title' => 'Systems Analysis and Design', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.52],
                    ['code' => 'MATH 201', 'title' => 'Linear Algebra & Numerical Methods', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.68],
                    ['code' => 'GE 109', 'title' => 'The Life and Works of Rizal', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'LIT 1', 'title' => 'Philippine Literature', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'PE 4', 'title' => 'Team Sports and Recreation', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                ],
            ],
            3 => [
                1 => [
                    ['code' => 'IT 301', 'title' => 'Advanced Database Systems', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.78],
                    ['code' => 'IT 302', 'title' => 'Web Systems and Technologies 2', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.80],
                    ['code' => 'IT 303', 'title' => 'Networking 2 (Enterprise Security)', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.76],
                    ['code' => 'IT 304', 'title' => 'Information Assurance & Security 1', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.72],
                    ['code' => 'IT 305', 'title' => 'Integrative Programming & Technologies', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.60],
                    ['code' => 'IT 306', 'title' => 'Mobile Applications Development', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.75],
                    ['code' => 'IT-E1', 'title' => 'IT Elective 1 (Cloud Infrastructure)', 'units' => 3.0, 'category' => 'Elective', 'difficulty' => 0.55],
                ],
                2 => [
                    ['code' => 'IT 307', 'title' => 'Information Assurance & Security 2', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.75],
                    ['code' => 'IT 308', 'title' => 'Systems Integration and Architecture', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.70],
                    ['code' => 'IT 309', 'title' => 'Cloud Computing & Virtualization', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.58],
                    ['code' => 'IT 310', 'title' => 'Capstone Project and Research 1', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.88],
                    ['code' => 'IT-E2', 'title' => 'IT Elective 2 (Data Science & Analytics)', 'units' => 3.0, 'category' => 'Elective', 'difficulty' => 0.65],
                    ['code' => 'IT-E3', 'title' => 'IT Elective 3 (Cybersecurity Auditing)', 'units' => 3.0, 'category' => 'Elective', 'difficulty' => 0.58],
                    ['code' => 'SOC 101', 'title' => 'Social and Professional Issues in IT', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                ],
            ],
            4 => [
                1 => [
                    ['code' => 'IT 401', 'title' => 'Capstone Project 2 (Implementation)', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.92],
                    ['code' => 'IT 402', 'title' => 'Systems Administration & Maintenance', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.55],
                    ['code' => 'IT-E4', 'title' => 'IT Elective 4 (Enterprise Solutions)', 'units' => 3.0, 'category' => 'Elective', 'difficulty' => 0.50],
                    ['code' => 'IT-E5', 'title' => 'IT Elective 5 (Artificial Intelligence)', 'units' => 3.0, 'category' => 'Elective', 'difficulty' => 0.70],
                    ['code' => 'MGT 101', 'title' => 'Technopreneurship 101', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.35],
                ],
                2 => [
                    ['code' => 'IT 404', 'title' => 'Industry Internship / Practicum (486 hrs)', 'units' => 6.0, 'category' => 'Practicum', 'difficulty' => 0.40],
                    ['code' => 'IT 405', 'title' => 'Seminars & Certification Colloquium', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.25],
                ],
            ],
        ],
        'BSCpE' => [
            1 => [
                1 => [
                    ['code' => 'ENG 101', 'title' => 'Calculus 1 (Differential Calculus)', 'units' => 4.0, 'category' => 'Math', 'difficulty' => 0.85],
                    ['code' => 'CPE 101', 'title' => 'Programming Logic and Design', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.72],
                    ['code' => 'CHEM 101', 'title' => 'Chemistry for Engineers', 'units' => 4.0, 'category' => 'Science', 'difficulty' => 0.65],
                    ['code' => 'GE 101', 'title' => 'Understanding the Self', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'GE 102', 'title' => 'Purposive Communication', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'PE 1', 'title' => 'Physical Fitness', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                    ['code' => 'NSTP 1', 'title' => 'NSTP 1', 'units' => 3.0, 'category' => 'NSTP', 'difficulty' => 0.10],
                ],
                2 => [
                    ['code' => 'ENG 102', 'title' => 'Calculus 2 (Integral Calculus)', 'units' => 4.0, 'category' => 'Math', 'difficulty' => 0.88],
                    ['code' => 'PHYS 101', 'title' => 'Physics for Engineers 1', 'units' => 4.0, 'category' => 'Science', 'difficulty' => 0.80],
                    ['code' => 'CPE 102', 'title' => 'Object Oriented Programming in C++', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.76],
                    ['code' => 'CPE 103', 'title' => 'Discrete Mathematics', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.70],
                    ['code' => 'GE 104', 'title' => 'Readings in Philippine History', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'PE 2', 'title' => 'Rhythmic Activities', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                    ['code' => 'NSTP 2', 'title' => 'NSTP 2', 'units' => 3.0, 'category' => 'NSTP', 'difficulty' => 0.10],
                ],
            ],
            2 => [
                1 => [
                    ['code' => 'ENG 201', 'title' => 'Differential Equations', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.82],
                    ['code' => 'CPE 201', 'title' => 'Data Structures and Algorithms', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.88],
                    ['code' => 'CPE 202', 'title' => 'Circuits 1 (DC Circuits)', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.84],
                    ['code' => 'PHYS 102', 'title' => 'Physics for Engineers 2', 'units' => 4.0, 'category' => 'Science', 'difficulty' => 0.75],
                    ['code' => 'GE 105', 'title' => 'The Contemporary World', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.18],
                    ['code' => 'PE 3', 'title' => 'Individual Sports', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                ],
                2 => [
                    ['code' => 'CPE 203', 'title' => 'Logic Circuits and Design', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.85],
                    ['code' => 'CPE 204', 'title' => 'Circuits 2 (AC Circuits)', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.82],
                    ['code' => 'CPE 205', 'title' => 'Electronic Devices & Circuits', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.80],
                    ['code' => 'ENG 202', 'title' => 'Engineering Data Analysis', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.65],
                    ['code' => 'GE 108', 'title' => 'Ethics', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'PE 4', 'title' => 'Team Sports', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                ],
            ],
            3 => [
                1 => [
                    ['code' => 'CPE 301', 'title' => 'Computer Architecture & Org', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.86],
                    ['code' => 'CPE 302', 'title' => 'Microprocessors & Microcontrollers', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.88],
                    ['code' => 'CPE 303', 'title' => 'Feedback & Control Systems', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.82],
                    ['code' => 'CPE 304', 'title' => 'Data Communications & Networking', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.70],
                    ['code' => 'GE 109', 'title' => 'Rizal\'s Life and Works', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                ],
                2 => [
                    ['code' => 'CPE 305', 'title' => 'Embedded Systems Design', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.90],
                    ['code' => 'CPE 306', 'title' => 'Digital Signal Processing', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.85],
                    ['code' => 'CPE 307', 'title' => 'Software Engineering for CpE', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.72],
                    ['code' => 'CPE 308', 'title' => 'CpE Research Methods / Capstone 1', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.85],
                ],
            ],
            4 => [
                1 => [
                    ['code' => 'CPE 401', 'title' => 'CpE Capstone Design 2', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.92],
                    ['code' => 'CPE 402', 'title' => 'Computer Engineering Practice', 'units' => 3.0, 'category' => 'Core', 'difficulty' => 0.50],
                    ['code' => 'CPE 403', 'title' => 'Emerging Technologies in CpE', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.65],
                ],
                2 => [
                    ['code' => 'CPE 404', 'title' => 'CpE On-the-Job Training / OJT', 'units' => 6.0, 'category' => 'Practicum', 'difficulty' => 0.35],
                ],
            ],
        ],
        'BSEE' => [
            1 => [
                1 => [
                    ['code' => 'ENG 101', 'title' => 'Calculus 1', 'units' => 4.0, 'category' => 'Math', 'difficulty' => 0.85],
                    ['code' => 'EE 101', 'title' => 'Fundamentals of Electrical Engineering', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.70],
                    ['code' => 'CHEM 101', 'title' => 'Chemistry for Engineers', 'units' => 4.0, 'category' => 'Science', 'difficulty' => 0.65],
                    ['code' => 'GE 101', 'title' => 'Understanding the Self', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.15],
                    ['code' => 'PE 1', 'title' => 'Physical Fitness', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                    ['code' => 'NSTP 1', 'title' => 'NSTP 1', 'units' => 3.0, 'category' => 'NSTP', 'difficulty' => 0.10],
                ],
                2 => [
                    ['code' => 'ENG 102', 'title' => 'Calculus 2', 'units' => 4.0, 'category' => 'Math', 'difficulty' => 0.88],
                    ['code' => 'PHYS 101', 'title' => 'Physics for Engineers 1', 'units' => 4.0, 'category' => 'Science', 'difficulty' => 0.80],
                    ['code' => 'EE 102', 'title' => 'Electrical Circuit Theory 1', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.85],
                    ['code' => 'GE 104', 'title' => 'Readings in Philippine History', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'PE 2', 'title' => 'Rhythmic Activities', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                    ['code' => 'NSTP 2', 'title' => 'NSTP 2', 'units' => 3.0, 'category' => 'NSTP', 'difficulty' => 0.10],
                ],
            ],
            2 => [
                1 => [
                    ['code' => 'EE 201', 'title' => 'Electrical Circuit Theory 2 (AC)', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.88],
                    ['code' => 'EE 202', 'title' => 'Electromagnetics', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.85],
                    ['code' => 'ENG 201', 'title' => 'Differential Equations', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.82],
                    ['code' => 'PHYS 102', 'title' => 'Physics for Engineers 2', 'units' => 4.0, 'category' => 'Science', 'difficulty' => 0.75],
                    ['code' => 'PE 3', 'title' => 'Individual Sports', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                ],
                2 => [
                    ['code' => 'EE 203', 'title' => 'Electrical Machines 1 (DC Machines)', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.86],
                    ['code' => 'EE 204', 'title' => 'Electronic Circuits & Devices', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.82],
                    ['code' => 'ENG 202', 'title' => 'Engineering Data Analysis', 'units' => 3.0, 'category' => 'Math', 'difficulty' => 0.65],
                    ['code' => 'GE 108', 'title' => 'Ethics', 'units' => 3.0, 'category' => 'GE', 'difficulty' => 0.20],
                    ['code' => 'PE 4', 'title' => 'Team Sports', 'units' => 2.0, 'category' => 'PE', 'difficulty' => 0.10],
                ],
            ],
            3 => [
                1 => [
                    ['code' => 'EE 301', 'title' => 'Electrical Machines 2 (AC Machines)', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.88],
                    ['code' => 'EE 302', 'title' => 'Feedback & Control Systems', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.84],
                    ['code' => 'EE 303', 'title' => 'Electrical Power Transmission', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.80],
                    ['code' => 'EE 304', 'title' => 'Power System Analysis', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.90],
                ],
                2 => [
                    ['code' => 'EE 305', 'title' => 'Power Distribution Systems', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.82],
                    ['code' => 'EE 306', 'title' => 'Electrical Equipment & Substation Design', 'units' => 4.0, 'category' => 'Major', 'difficulty' => 0.85],
                    ['code' => 'EE 307', 'title' => 'EE Research Methods & Capstone 1', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.85],
                ],
            ],
            4 => [
                1 => [
                    ['code' => 'EE 401', 'title' => 'EE Capstone Project 2', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.92],
                    ['code' => 'EE 402', 'title' => 'Philippine Electrical Code (PEC)', 'units' => 3.0, 'category' => 'Major', 'difficulty' => 0.70],
                ],
                2 => [
                    ['code' => 'EE 404', 'title' => 'EE On-the-Job Training / OJT', 'units' => 6.0, 'category' => 'Practicum', 'difficulty' => 0.35],
                ],
            ],
        ],
    ];

    /**
     * Generate the complete enrolled subjects analysis & difficulty heatmap for a student record.
     *
     * @return array{
     *     subjects: list<array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     cohort_heatmap: array<string, mixed>
     * }
     */
    public function forStudentRecord(object $student): array
    {
        $programCode = strtoupper((string) ($student->program?->program_code ?? ($student->program_code ?? 'BSIT')));
        if (! isset(self::CURRICULUM[$programCode])) {
            $programCode = 'BSIT';
        }

        $yearLevel = max(1, min(4, (int) ($student->year_level ?? 1)));

        // Determine semester number (1 or 2)
        $semNumber = 1;
        if (isset($student->semester)) {
            $semName = strtolower((string) ($student->semester->semester_name ?? ''));
            if (str_contains($semName, 'second') || (int) ($student->semester->semester_order ?? 0) === 2) {
                $semNumber = 2;
            }
        } elseif (isset($student->semester_id)) {
            $semNumber = ($student->semester_id % 2 === 0) ? 2 : 1;
        }

        $catalog = self::CURRICULUM[$programCode][$yearLevel][$semNumber]
            ?? self::CURRICULUM[$programCode][$yearLevel][1]
            ?? self::CURRICULUM['BSIT'][1][1];

        // Sort descending by intrinsic difficulty
        usort($catalog, fn ($a, $b) => $b['difficulty'] <=> $a['difficulty']);

        $failedCount = max(0, (int) ($student->failed_subjects ?? 0));
        $gpa = (float) ($student->gpa ?? 2.0);

        // Calculate conditional / warning subjects count (attendance not available in dataset)
        $warningCount = 0;
        if ($failedCount > 0) {
            $warningCount = 1;
        } elseif ($gpa >= 2.50) {
            $warningCount = $gpa >= 2.85 ? 2 : 1;
        }

        $studentSubjects = [];
        $failedAssigned = 0;
        $warningAssigned = 0;
        $strugglingNames = [];

        foreach ($catalog as $subject) {
            $isFailed = false;
            $isWarning = false;

            if ($failedAssigned < $failedCount) {
                $isFailed = true;
                $failedAssigned++;
                $status = 'failed';
                $statusLabel = 'Critical / Failed';
                $subjectRisk = 'Critical';
                $isAtRisk = true;
                $grade = 5.00;
                $color = '#dc2626'; // Red
                $bgColor = '#fef2f2';
                $borderColor = '#fca5a5';
                $strugglingNames[] = $subject['code'].' ('.$subject['title'].')';
                $recommendation = 'Critical prerequisite. Priority for remedial tutoring and immediate retake scheduling.';
            } elseif ($warningAssigned < $warningCount) {
                $isWarning = true;
                $warningAssigned++;
                $status = 'warning';
                $statusLabel = 'High Risk / Warning';
                $subjectRisk = 'High Risk';
                $isAtRisk = true;
                $grade = $gpa >= 3.0 ? 3.00 : 2.75;
                $color = '#ea580c'; // Orange
                $bgColor = '#fff7ed';
                $borderColor = '#fdba74';
                $strugglingNames[] = $subject['code'];
                $recommendation = 'High academic risk. Near-failing grade standing; requires peer coaching and close lab monitoring.';
            } else {
                $status = 'passed';
                $statusLabel = 'Good Standing';
                $subjectRisk = 'Good';
                $isAtRisk = false;
                $grade = max(1.25, round($gpa - (rand(0, 4) * 0.15), 2));
                if ($grade > 2.50) {
                    $grade = 2.25;
                }
                $color = '#059669'; // Emerald
                $bgColor = '#ecfdf5';
                $borderColor = '#a7f3d0';
                $recommendation = 'Good standing. Regular curriculum progress.';
            }

            // Categorize intrinsic difficulty level
            $diffRating = (float) $subject['difficulty'];
            $diffLabel = match (true) {
                $diffRating >= 0.80 => 'Critical',
                $diffRating >= 0.65 => 'High',
                $diffRating >= 0.40 => 'Moderate',
                default => 'Standard',
            };

            $diffColor = match (true) {
                $diffRating >= 0.80 => '#b91c1c', // deep red
                $diffRating >= 0.65 => '#c2410c', // orange-red
                $diffRating >= 0.40 => '#d97706', // amber
                default => '#059669', // green
            };

            $studentSubjects[] = [
                'code' => $subject['code'],
                'title' => $subject['title'],
                'units' => $subject['units'],
                'category' => $subject['category'],
                'difficulty' => $diffRating,
                'difficulty_pct' => (int) round($diffRating * 100),
                'difficulty_label' => $diffLabel,
                'difficulty_color' => $diffColor,
                'status' => $status,
                'status_label' => $statusLabel,
                'subject_risk' => $subjectRisk,
                'is_at_risk' => $isAtRisk,
                'grade' => number_format($grade, 2),
                'is_failed' => $isFailed,
                'is_warning' => $isWarning,
                'color' => $color,
                'bg_color' => $bgColor,
                'border_color' => $borderColor,
                'recommendation' => $recommendation,
            ];
        }

        $atRiskSubjects = array_values(array_filter($studentSubjects, fn ($s) => $s['is_at_risk']));
        $goodSubjects = array_values(array_filter($studentSubjects, fn ($s) => ! $s['is_at_risk']));
        $totalUnits = array_sum(array_column($studentSubjects, 'units'));
        $passedCount = count($goodSubjects);
        $avgDifficulty = count($studentSubjects) > 0
            ? round(array_sum(array_column($studentSubjects, 'difficulty')) / count($studentSubjects), 2)
            : 0.0;

        return [
            'subjects' => $studentSubjects,
            'at_risk_subjects' => $atRiskSubjects,
            'good_subjects' => $goodSubjects,
            'summary' => [
                'total_subjects' => count($studentSubjects),
                'total_units' => $totalUnits,
                'at_risk_count' => count($atRiskSubjects),
                'good_count' => $passedCount,
                'failed_count' => $failedAssigned,
                'warning_count' => $warningAssigned,
                'passed_count' => max(0, $passedCount),
                'avg_difficulty' => $avgDifficulty,
                'avg_difficulty_pct' => (int) round($avgDifficulty * 100),
                'highest_difficulty' => $studentSubjects[0] ?? null,
                'struggling_subjects_text' => implode(', ', $strugglingNames) ?: 'None (All subjects in good standing)',
            ],
            'cohort_heatmap' => $this->cohortHeatmap(),
        ];
    }

    /**
     * Cohort Subject Difficulty Matrix (Program Code x Year Level average failed subjects).
     */
    public function cohortHeatmap(): array
    {
        $levels = [1, 2, 3, 4];
        $grouped = DB::table('student_records as students')
            ->join('programs', 'programs.id', '=', 'students.program_id')
            ->groupBy('programs.program_code', 'students.year_level')
            ->orderBy('programs.program_code')
            ->orderBy('students.year_level')
            ->get([
                'programs.program_code',
                'students.year_level',
                DB::raw('AVG(students.failed_subjects) as failed_avg'),
                DB::raw('COUNT(*) as students'),
            ]);

        $rows = [];
        $max = 0.0;
        foreach ($grouped as $cell) {
            $program = (string) $cell->program_code;
            $level = (int) $cell->year_level;
            $failed = round((float) $cell->failed_avg, 2);
            $max = max($max, $failed);
            $rows[$program]['program'] = $program;
            $rows[$program]['cells'][$level] = [
                'failed' => $failed,
                'students' => (int) $cell->students,
            ];
        }

        return [
            'levels' => $levels,
            'max' => max(0.01, $max),
            'rows' => array_values($rows),
        ];
    }
}
