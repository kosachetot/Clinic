<?php

return [
    // Divisions come from the HRIS divisions table. Use this to show a short code
    // on the dashboard instead of the long HRIS office name: 'HRIS office' => 'shown as'
    'division_aliases' => [
        'PROV. DOH OFFICE' => 'PDOHO',
    ],

    // Age groups used by the charts: [min, max], inclusive
    'age_groups' => [
        '18-29' => [18, 29],
        '30-39' => [30, 39],
        '40-49' => [40, 49],
        '50-60' => [50, 60],
        '60 up' => [61, 150],
    ],

    // Laboratory limits (SI units) from the Annual Medical Exam sheet
    'lab' => [
        'hba1c_max' => 6.5,        // above this counts as diabetes mellitus
        'cholesterol_max' => 5.2,  // at or above this counts as high
        'triglyceride_max' => 1.7, // at or above this counts as high
        'hdl_min' => 1.55,         // at or below this counts as low
        'ldl_max' => 2.6,          // at or above this counts as high
    ],
];
