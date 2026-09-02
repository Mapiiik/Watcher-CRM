<?php
return [
    'Users.controller' => 'AppUsers',
    'Users.table' => 'AppUsers',
    'Users.Registration.active' => false,
    'Users.Superuser.allowedToChangePasswords' => true,
    'Users.Superuser.allowedToChangeSettings' => true,
    'Users.Social.login' => false,
    // The root, which is where the page a user starts on is decided - the dashboard unless
    // they have settled on another one for themselves. Naming a page here instead would put
    // that choice out of reach of everybody who arrives by signing in.
    'Auth.AuthenticationComponent.loginRedirect' => '/',
    'OAuth.providers.google.options.clientId' => env('GOOGLE_OAUTH_CLIENT_ID', null),
    'OAuth.providers.google.options.clientSecret' => env('GOOGLE_OAUTH_CLIENT_SECRET', null),
];
