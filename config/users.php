<?php
return [
    'Users.controller' => 'AppUsers',
    'Users.table' => 'AppUsers',
    'Users.Registration.active' => false,
    'Users.Superuser.allowedToChangePasswords' => true,
    'Users.Superuser.allowedToChangeSettings' => true,
    'Users.Social.login' => false,
    // The dashboard's own route, rather than the fallback one. The Dashboard plugin's
    // assets are linked into the webroot under its own name, so `/dashboard` is a directory
    // the web server answers itself before the router ever sees it.
    'Auth.AuthenticationComponent.loginRedirect' => '/',
    'OAuth.providers.google.options.clientId' => env('GOOGLE_OAUTH_CLIENT_ID', null),
    'OAuth.providers.google.options.clientSecret' => env('GOOGLE_OAUTH_CLIENT_SECRET', null),
];
