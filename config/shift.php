<?php

return [
    'timezone' => 'Asia/Jakarta',

    // Urutan Rotasi blok malam
    'rotation' => ['rizky', 'rayhand', 'azhar'],

    // Anggota rotasi yang merupakan pemilik akun Xibra ini
    'owner_member' => 'rizky',

    'members' => [
        'rizky' => 'Rizky',
        'rayhand' => 'Rayhand',
        'azhar' => 'Azhar',
    ],

    // Jumlah malam dalam satu blok. Shift 3 dimulai pukul 20.00 dan selesai 05.00.
    'block_nights' => 5,

    // Shift yang dicatat manual lewat /shifts
    'manual_codes' => [
        '1' => ['name' => 'Shift 1', 'start' => '06:00:00', 'end' => '15:00:00'],
        '2' => ['name' => 'Shift 2', 'start' => '09:00:00', 'end' => '18:00:00']
    ],
];
