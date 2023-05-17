<?php
return [
    'Users.controller' => 'AppUsers',
    'Users.table' => 'AppUsers',
    'Users.Registration.active' => false,
    'Users.Superuser.allowedToChangePasswords' => true,
    'Users.Superuser.allowedToChangeSettings' => true,
    'Users.Social.login' => false,
    'Auth.AuthenticationComponent.loginRedirect' => '/users/profile',
    'OAuth.providers.google.options.clientId' => env('GOOLE_OAUTH_CLIENT_ID', null),
    'OAuth.providers.google.options.clientSecret' => env('GOOLE_OAUTH_CLIENT_SECRET', null),
];
