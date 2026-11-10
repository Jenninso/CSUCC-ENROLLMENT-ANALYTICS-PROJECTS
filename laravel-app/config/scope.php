<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System academic scope
    |--------------------------------------------------------------------------
    |
    | This enrollment analytics system is intentionally limited to a single
    | college and program at CSUCC Cabadbaran Campus.
    |
    */
    'college_name' => 'College of Engineering and Information Technology',
    'college_code' => 'CEIT',
    'department_name' => 'Information Technology',
    'department_code' => 'IT',
    'program_name' => 'BS Information Technology',
    'program_code' => 'BSIT',
    'degree_level' => 'Bachelor',

    /*
    | CEIT has two student groups. Information Technology and Engineering
    | are separate departments and courses.
    */
    'departments' => [
        'IT' => 'Information Technology',
        'CpE' => 'Computer Engineering',
        'EE' => 'Electrical Engineering',
    ],
    'programs' => [
        'BSIT' => [
            'name' => 'BS Information Technology',
            'department' => 'IT',
            'group' => 'Information Technology',
        ],
        'BSCpE' => [
            'name' => 'BS Computer Engineering',
            'department' => 'CpE',
            'group' => 'Engineering',
        ],
        'BSEE' => [
            'name' => 'BS Electrical Engineering',
            'department' => 'EE',
            'group' => 'Engineering',
        ],
    ],
];
