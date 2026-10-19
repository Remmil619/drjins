<?php

return [
    'name' => "Dr. Jn's Children's Clinic",
    'address' => 'Abuyog, Leyte, Philippines',
    'phone' => env('CLINIC_PHONE', '09358608796'),
    'email' => env('CLINIC_EMAIL'),
    'services' => [
        'child-checkup' => ['name' => 'Child checkup', 'description' => 'Schedule a clinic consultation for your child and share your concerns with the doctor.', 'icon' => '+'],
    ],
    'schedule' => array_replace(array_fill(1, 5, ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00']), [6 => ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00']]),
];
