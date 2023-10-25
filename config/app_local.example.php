<?php

use Cake\Mailer\Transport\MailTransport;

/*
 * Local configuration file to provide any overrides to your app.php configuration.
 * Copy and save this file as app_local.php and make changes as required.
 * Note: It is not recommended to commit files with credentials such as app_local.php
 * into source code version control.
 */
return [
    /*
     * Debug Level:
     *
     * Production Mode:
     * false: No error messages, errors, or warnings shown.
     *
     * Development Mode:
     * true: Errors and warnings shown.
     */
    'debug' => filter_var(env('DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT', '__SALT__'),
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * See app.php for more configuration options.
     */
    'Datasources' => [
        'default' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Postgres',
            'host' => 'localhost',
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',

            'username' => 'postgres',
            'password' => 'postgres',

            'database' => 'watcher_crm',
            /*
             * If not using the default 'public' schema with the PostgreSQL driver
             * set it here.
             */
            //'schema' => 'myapp',

            /*
             * You can use a DSN string to set the entire configuration
             */
            'url' => env('DATABASE_URL', null),
        ],
        'ruian' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Postgres',
            'host' => 'localhost',
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',

            'username' => 'postgres',
            'password' => 'postgres',

            'database' => 'watcher_crm',
            /*
             * If not using the default 'public' schema with the PostgreSQL driver
             * set it here.
             */
            //'schema' => 'myapp',

            /*
             * You can use a DSN string to set the entire configuration
             */
            'url' => env('DATABASE_RUIAN_URL', null),
        ],
        'radius' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Postgres',
            'host' => 'localhost',
            /*
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',

            'username' => 'postgres',
            'password' => 'postgres',

            'database' => 'watcher_crm',
            /*
             * If not using the default 'public' schema with the PostgreSQL driver
             * set it here.
             */
            //'schema' => 'myapp',

            /*
             * You can use a DSN string to set the entire configuration
             */
            'url' => env('DATABASE_RADIUS_URL', null),
        ],

        /*
         * The test connection is used during the test suite.
         */
        'test' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Postgres',
            'host' => 'localhost',
            //'port' => 'non_standard_port_number',
            'username' => 'postgres',
            'password' => 'postgres',
            'database' => 'watcher_crm_test',
            //'schema' => 'public',
            'url' => env('DATABASE_TEST_URL', null),
        ],
        'test_ruian' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Postgres',
            'host' => 'localhost',
            //'port' => 'non_standard_port_number',
            'username' => 'postgres',
            'password' => 'postgres',
            'database' => 'watcher_crm_test',
            //'schema' => 'public',
            'url' => env('DATABASE_TEST_URL', null),
        ],
        'test_radius' => [
            'className' => 'Cake\Database\Connection',
            'driver' => 'Cake\Database\Driver\Postgres',
            'host' => 'localhost',
            //'port' => 'non_standard_port_number',
            'username' => 'postgres',
            'password' => 'postgres',
            'database' => 'watcher_crm_test',
            //'schema' => 'public',
            'url' => env('DATABASE_TEST_URL', null),
        ],
    ],

    /*
     * Email configuration.
     *
     * Host and credential configuration in case you are using SmtpTransport
     *
     * See app.php for more configuration options.
     */
    'EmailTransport' => [
        'default' => [
            'className' => MailTransport::class,
            'host' => 'localhost',
            'port' => 25,
            'timeout' => 30,
            'username' => null,
            'password' => null,
            'client' => null,
            'tls' => false,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
        'invoices' => [
            'className' => MailTransport::class,
            'host' => 'localhost',
            'port' => 25,
            'timeout' => 30,
            'username' => null,
            'password' => null,
            'client' => null,
            'tls' => false,
            'url' => env('EMAIL_TRANSPORT_INVOICES_URL', null),
        ],
        'contracts' => [
            'className' => MailTransport::class,
            'host' => 'localhost',
            'port' => 25,
            'timeout' => 30,
            'username' => null,
            'password' => null,
            'client' => null,
            'tls' => false,
            'url' => env('EMAIL_TRANSPORT_CONTRACTS_URL', null),
        ],
    ],
    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => [
                (string)env('EMAIL_DEFAULT_SENDER_EMAIL', 'default@localhost')
                => (string)env('EMAIL_DEFAULT_SENDER_NAME', 'Default'),
            ],
            'cc' => env('EMAIL_DEFAULT_COPY_TO_EMAIL', null),
            'replyTo' => env('EMAIL_DEFAULT_REPLY_TO_EMAIL', null),
            /*
             * Will by default be set to config value of App.encoding, if that exists otherwise to UTF-8.
             */
            'charset' => 'utf-8',
            'headerCharset' => 'utf-8',
        ],
        'invoices' => [
            'transport' => 'invoices',
            'from' => [
                (string)env('EMAIL_INVOICES_SENDER_EMAIL', 'default@localhost')
                => (string)env('EMAIL_INVOICES_SENDER_NAME', 'Default'),
            ],
            'cc' => env('EMAIL_INVOICES_COPY_TO_EMAIL', null),
            'replyTo' => env('EMAIL_INVOICES_REPLY_TO_EMAIL', null),
            /*
             * Will by default be set to config value of App.encoding, if that exists otherwise to UTF-8.
             */
            'charset' => 'utf-8',
            'headerCharset' => 'utf-8',
        ],
        'contracts' => [
            'transport' => 'contracts',
            'from' => [
                (string)env('EMAIL_CONTRACTS_SENDER_EMAIL', 'default@localhost')
                => (string)env('EMAIL_CONTRACTS_SENDER_NAME', 'Default'),
            ],
            'cc' => env('EMAIL_CONTRACTS_COPY_TO_EMAIL', null),
            'replyTo' => env('EMAIL_CONTRACTS_REPLY_TO_EMAIL', null),
            /*
             * Will by default be set to config value of App.encoding, if that exists otherwise to UTF-8.
             */
            'charset' => 'utf-8',
            'headerCharset' => 'utf-8',
        ],
    ],
];
