<?php 

return [
    'db_host' => 'localhost:3306',
    'db_name' => 'training',
    'db_user' => 'training',
    'db_pass' => 'password',
    "default_locale" => "hu_HU", 
    "available_locales" => [
        /* "hu_HU", */
        "en_US"
    ],
    'session' => [
        'driver' => 'file',
        'config' => [
            'folder' => realpath(__DIR__)."/storage/sessions"
        ]
    ],
    "mail" => [
        "username" => "6a144db2cf78e8",
        "password" => "98b34ed8a30e56",
        "host" => "smtp.mailtrap.io",
        "port" => 465
    ]
];
