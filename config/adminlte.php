<?php
return [
    'Theme' => [
        'title' => env('APP_COMPANY', 'ISP') . ' | CRM',
        'logo' => [
            'mini' => '<b>CRM</b>',
            'large' => env('APP_COMPANY', 'ISP') . ' | <b>CRM</b>',
        ],
        'login' => [
            'show_remember' => true,
            'show_register' => false,
            'show_social' => false
        ],
        'folder' => ROOT,
        'skin' => 'blue'
    ]
];
