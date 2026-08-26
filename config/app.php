<?php

use App\BusinessRegister\Source\AresSource;
use App\BusinessRegister\Source\SudregSource;
use App\BusinessRegister\Source\ViesSource;
use Cake\Cache\Engine\FileEngine;
use Cake\Database\Connection;
use Cake\Database\Driver\Postgres;
use Cake\Log\Engine\FileLog;
use Cake\Mailer\Transport\MailTransport;
use Maps\Geocoder\AddressRegistryGeocoder;
use Maps\Geocoder\OpenStreetMapGeocoder;
use Radius\HistoricalConnections\RadiusSource;
use function Cake\Core\env;

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
     * Configure basic information about the application.
     *
     * - namespace - The namespace to find app classes under.
     * - defaultLocale - The default locale for translation, formatting currencies and numbers, date and time.
     * - encoding - The encoding used for HTML + database connections.
     * - base - The base directory the app resides in. If false this
     *   will be auto-detected.
     * - dir - Name of app directory.
     * - webroot - The webroot directory.
     * - wwwRoot - The file path to webroot.
     * - baseUrl - To configure CakePHP to *not* use mod_rewrite and to
     *   use CakePHP pretty URLs, remove these .htaccess
     *   files:
     *      /.htaccess
     *      /webroot/.htaccess
     *   And uncomment the baseUrl key below.
     * - fullBaseUrl - SECURITY: A base URL to use for absolute links.
     *   IMPORTANT: This MUST be set in production to prevent Host Header Injection attacks
     *   that can compromise password reset and other security-critical features.
     *   Set this via APP_FULL_BASE_URL environment variable or directly in config.
     *   Example: 'https://example.com'
     *   When not set, the application will throw an exception in production mode.
     * - imageBaseUrl - Web path to the public images/ directory under webroot.
     * - cssBaseUrl - Web path to the public css/ directory under webroot.
     * - jsBaseUrl - Web path to the public js/ directory under webroot.
     * - paths - Configure paths for non class-based resources. Supports the
     *   `plugins`, `templates`, `locales` subkeys, which allow the definition of
     *   paths for plugins, view templates and locale files respectively.
     */
    'App' => [
        'namespace' => 'App',
        'encoding' => env('APP_ENCODING', 'UTF-8'),
        'defaultLocale' => env('APP_DEFAULT_LOCALE', 'en_US'),
        'defaultTimezone' => env('APP_DEFAULT_TIMEZONE', 'UTC'),
        // Who the installation belongs to, and how it writes dates, times and money.
        'company' => env('APP_COMPANY', 'ISP'),
        'timeFormat' => env('APP_TIME_FORMAT', null),
        'dateFormat' => env('APP_DATE_FORMAT', null),
        'defaultCurrency' => env('APP_DEFAULT_CURRENCY', null),
        'base' => false,
        'dir' => 'src',
        'webroot' => 'webroot',
        'wwwRoot' => WWW_ROOT,
        //'baseUrl' => env('SCRIPT_NAME'),
        'fullBaseUrl' => env(
            'APP_FULL_BASE_URL',
            env('FULL_BASE_URL', false), // FULL_BASE_URL for backward compatibility
        ),
        'imageBaseUrl' => 'img/',
        'cssBaseUrl' => 'css/',
        'jsBaseUrl' => 'js/',
        'paths' => [
            'plugins' => [ROOT . DS . 'plugins' . DS],
            'templates' => [ROOT . DS . 'templates' . DS],
            'locales' => [RESOURCES . 'locales' . DS],
        ],
    ],

    /*
     * Security and encryption configuration
     *
     * - salt - A random string used in security hashing methods.
     *   The salt value is also used as the encryption key.
     *   You should treat it as extremely sensitive data.
     */
    'Security' => [
        'salt' => env('SECURITY_SALT'),
    ],

    /*
     * Apply timestamps with the last modified time to static assets (js, css, images).
     * Will append a querystring parameter containing the time the file was modified.
     * This is useful for busting browser caches.
     *
     * Set to true to apply timestamps when debug is true. Set to 'force' to always
     * enable timestamping regardless of debug value.
     */
    'Asset' => [
        //'timestamp' => true,
        // 'cacheTime' => '+1 year'
    ],

    /*
     * Configure the cache adapters.
     */
    'Cache' => [
        'default' => [
            'className' => FileEngine::class,
            'path' => CACHE,
            'url' => env('CACHE_DEFAULT_URL', null),
        ],

        /*
         * Configure the cache used for general framework caching.
         * Translation cache files are stored with this configuration.
         * Duration will be set to '+2 minutes' in bootstrap.php when debug = true
         * If you set 'className' => 'Null' core cache will be disabled.
         */
        '_cake_translations_' => [
            'className' => FileEngine::class,
            'prefix' => 'myapp_cake_translations_',
            'path' => CACHE . 'persistent' . DS,
            'serialize' => true,
            'duration' => '+1 years',
            'url' => env('CACHE_CAKECORE_URL', null),
        ],

        /*
         * Configure the cache for model and datasource caches. This cache
         * configuration is used to store schema descriptions, and table listings
         * in connections.
         * Duration will be set to '+2 minutes' in bootstrap.php when debug = true
         */
        '_cake_model_' => [
            'className' => FileEngine::class,
            'prefix' => 'myapp_cake_model_',
            'path' => CACHE . 'models' . DS,
            'serialize' => true,
            'duration' => '+1 years',
            'url' => env('CACHE_CAKEMODEL_URL', null),
        ],

        /*
         * Configure the cache for API Client
         */
        'api_client' => [
            'className' => FileEngine::class,
            'prefix' => 'api_client_',
            'path' => CACHE,
            'serialize' => true,
            'duration' => '+5 minutes',
            'url' => env('CACHE_APICLIENT_URL', null),
        ],
        'addresses_api' => [
            'className' => FileEngine::class,
            'prefix' => 'api_addresses_',
            'path' => CACHE . 'addresses' . DS,
            'serialize' => true,
            'duration' => '+1 day',
            'url' => env('CACHE_ADDRESSES_URL', null),
        ],
        'business_register' => [
            'className' => FileEngine::class,
            'prefix' => 'business_register_',
            'path' => CACHE . 'business_register' . DS,
            'serialize' => true,
            'duration' => '+1 day',
            'url' => env('CACHE_BUSINESS_REGISTER_URL', null),
        ],
    ],

    /*
     * Configure the Error and Exception handlers used by your application.
     *
     * By default errors are displayed using Debugger, when debug is true and logged
     * by Cake\Log\Log when debug is false.
     *
     * In CLI environments exceptions will be printed to stderr with a backtrace.
     * In web environments an HTML page will be displayed for the exception.
     * With debug true, framework errors like Missing Controller will be displayed.
     * When debug is false, framework errors will be coerced into generic HTTP errors.
     *
     * Options:
     *
     * - `errorLevel` - int - The level of errors you are interested in capturing.
     * - `trace` - boolean - Whether backtraces should be included in
     *   logged errors/exceptions.
     * - `log` - boolean - Whether you want exceptions logged.
     * - `exceptionRenderer` - string - The class responsible for rendering uncaught exceptions.
     *   The chosen class will be used for both CLI and web environments. If you want different
     *   classes used in CLI and web environments you'll need to write that conditional logic as well.
     *   The conventional location for custom renderers is in `src/Error`. Your exception renderer needs to
     *   implement the `render()` method and return either a string or Http\Response.
     *   `errorRenderer` - string - The class responsible for rendering PHP errors. The selected
     *   class will be used for both web and CLI contexts. If you want different classes for each environment
     *   you'll need to write that conditional logic as well. Error renderers need to
     *   to implement the `Cake\Error\ErrorRendererInterface`.
     * - `skipLog` - array - List of exceptions to skip for logging. Exceptions that
     *   extend one of the listed exceptions will also be skipped for logging.
     *   E.g.:
     *   `'skipLog' => ['Cake\Http\Exception\NotFoundException', 'Cake\Http\Exception\UnauthorizedException']`
     * - `extraFatalErrorMemory` - int - The number of megabytes to increase the memory limit by
     *   when a fatal error is encountered. This allows
     *   breathing room to complete logging or error handling.
     * - `ignoredDeprecationPaths` - array - A list of glob-compatible file paths that deprecations
     *   should be ignored in. Use this to ignore deprecations for plugins or parts of
     *   your application that still emits deprecations.
     * - `traceFormat` - when logging errors, List of `'array'`, `'points'`, `'shortPoints'`, defaults to `shortPoints`.
     */
    'Error' => [
        'errorLevel' => E_ALL,
        'skipLog' => [],
        'log' => true,
        'trace' => true,
        'ignoredDeprecationPaths' => [
            'vendor/cakephp/cakephp/src/Core/PluginCollection.php', // TODO - waiting for CakePHP/Queue 2.2.1 release
        ],
        'traceFormat' => null,
    ],

    /*
     * Debugger configuration
     *
     * Define development error values for Cake\Error\Debugger
     *
     * - `editor` Set the editor URL format you want to use.
     *   By default atom, emacs, macvim, phpstorm, sublime, textmate, and vscode are
     *   available. You can add additional editor link formats using
     *   `Debugger::addEditor()` during your application bootstrap.
     * - `editorBasePath` - The base path to your project for editor integration.
     *   Used to generate file links in stack traces.
     * - `outputMask` A mapping of `key` to `replacement` values that
     *   `Debugger` should replace in dumped data and logs generated by `Debugger`.
     */
    'Debugger' => [
        'editor' => 'vscode',
    ],

    /*
     * Email configuration.
     *
     * By defining transports separately from delivery profiles you can easily
     * re-use transport configuration across multiple profiles.
     *
     * You can specify multiple configurations for production, development and
     * testing.
     *
     * Each transport needs a `className`. Valid options are as follows:
     *
     *  Mail   - Send using PHP mail function
     *  Smtp   - Send using SMTP
     *  Debug  - Do not send the email, just return the result
     *
     * You can add custom transports (or override existing transports) by adding the
     * appropriate file to src/Mailer/Transport. Transports should be named
     * 'YourTransport.php', where 'Your' is the name of the transport.
     */
    'EmailTransport' => [
        'default' => [
            'className' => MailTransport::class,
            /*
             * The keys host, port, timeout, username, password, client and tls
             * are used in SMTP transports
             */
            'host' => 'localhost',
            'port' => 25,
            'timeout' => 30,
            /*
             * It is recommended to set these options through your environment or app_local.php
             */
            //'username' => null,
            //'password' => null,
            'client' => null,
            'tls' => false,
            'url' => env('EMAIL_TRANSPORT_DEFAULT_URL', null),
        ],
    ],

    /*
     * Who is told what: `emails` is sent what the application has to report - a task closed,
     * accounts changed, readings taken - and `errorEmails` is told when something that runs
     * unattended has failed.
     *
     * Two audiences, because whoever is on call is rarely whoever reads the overnight paperwork.
     * Until a deployment names the second, a failure goes where it went before the two were told
     * apart.
     *
     * Read from the environment here and nowhere else, so that the day it comes from somewhere
     * else - the database, a vault - one line changes. Empty is a deployment saying nobody is to
     * be told, which is allowed: a failure is logged and printed either way, and a report is
     * simply left unsent.
     */
    'Report' => [
        'emails' => preg_split('/\s+/', trim((string)env('REPORT_EMAILS', '')), -1, PREG_SPLIT_NO_EMPTY) ?: [],
        'errorEmails' => preg_split(
            '/\s+/',
            trim((string)(env('ERROR_EMAILS') ?: env('REPORT_EMAILS', ''))),
            -1,
            PREG_SPLIT_NO_EMPTY,
        ) ?: [],
    ],

    /*
     * What the installation is, as opposed to how it is wired up.
     *
     * The environment is read here and nowhere else, so a value has one spelling, one default and
     * one place to change. Anything read straight from `env()` further in has the default written
     * out again wherever it is read, which is how two of them come to disagree.
     */
    'Customers' => [
        // added to the customer's `nid` to get the number written on invoices
        'series' => (int)env('CUSTOMER_SERIES', '0'),
    ],

    'Data' => [
        'root' => (string)env('DATA_ROOT', ROOT . DS . 'data'),
    ],

    'Phones' => [
        // the region numbers without a country prefix are read as; nothing named means none assumed
        'defaultRegion' => trim((string)env('APP_DEFAULT_PHONE_REGION', '')) ?: null,
        'stripPrefixForSummary' => filter_var(
            env('STRIP_PHONE_PREFIX_FOR_SUMMARY_TEXT', false),
            FILTER_VALIDATE_BOOLEAN,
        ),
    ],

    'Billings' => [
        'roundingPlaces' => (int)env('BILLING_PERIOD_ROUNDING_PLACES', '2'),
        'roundingType' => (string)env('BILLING_PERIOD_ROUNDING_TYPE', 'HALF_UP'),
    ],

    'IpAddresses' => [
        'minimumDaysSinceLastUse' => (int)env(
            'MINIMUM_NUMBER_OF_DAYS_SINCE_LAST_USE_FOR_AVAILABLE_IP_ADDRESSES',
            '365',
        ),
        'firstAvailableOffset' => (int)env('OFFSET_OF_FIRST_AVAILABLE_IP_ADDRESS', '1'),
    ],

    /*
     * The services this one talks to. An empty address or key is not configured, and every client
     * says so in its own way rather than reaching out with nothing.
     */
    'Nms' => [
        'url' => (string)env('WATCHER_NMS_URL', ''),
        'key' => (string)env('WATCHER_NMS_KEY', ''),
    ],

    'Agent' => [
        'url' => rtrim((string)env('WATCHER_AGENT_URL', ''), '/'),
        'token' => (string)env('WATCHER_AGENT_TOKEN', ''),
        // what the RADIUS server expects of whoever asks it to drop a session
        'radiusSecret' => (string)env('RADIUS_SECRET', ''),
    ],

    'Addresses' => [
        'url' => rtrim((string)env('ADDRESSES_API_URL', ''), '/'),
        'key' => (string)env('ADDRESSES_API_KEY', ''),
    ],

    /*
     * Maps
     *
     * `provider` selects the mapping stack, `geocoder` the class the address search asks - here the
     * national address registry this application already matches its addresses against.
     *
     * Everything the plugin can decide for itself - the base layers, the default view, what the map
     * lets the user do - lives in plugins/Maps/config/maps.php. Name a key here to override it.
     */
    'Maps' => [
        'provider' => env('MAP_PROVIDER', 'osm'),
        // The registry knows this country's addresses exactly; OpenStreetMap answers for the
        // addresses it does not carry at all.
        'geocoder' => [
            AddressRegistryGeocoder::class,
            OpenStreetMapGeocoder::class,
        ],
        'addressRegistry' => [
            'url' => rtrim((string)env('ADDRESSES_API_URL', ''), '/'),
            'key' => (string)env('ADDRESSES_API_KEY', ''),
            // An address says which country it is in and that is the one asked. This is for
            // whoever asks about no address in particular.
            'defaultCountries' => env('ADDRESSES_API_COUNTRIES', 'cz,hr'),
        ],
        'nominatim' => [
            'userAgent' => env('NOMINATIM_USER_AGENT', 'Watcher CRM'),
        ],
    ],

    'Sms' => [
        'url' => (string)env('ANDROID_SMS_GATEWAY_URL', ''),
        'login' => (string)env('ANDROID_SMS_GATEWAY_LOGIN', ''),
        'password' => (string)env('ANDROID_SMS_GATEWAY_PASSWORD', ''),
        'passphrase' => (string)env('ANDROID_SMS_GATEWAY_PASSPHRASE', ''),
    ],

    'SledovaniTv' => [
        'username' => (string)env('DEBTORS_SLEDOVANITV_USERNAME', ''),
        'password' => (string)env('DEBTORS_SLEDOVANITV_PASSWORD', ''),
    ],

    /*
     * What the plugins are told about this installation. Named after the plugin so it is plain
     * whose it is, and read here so a plugin does not reach into the environment of its own.
     */
    'Bookkeeping' => [
        'debtors' => [
            // where the blocking is carried out, and what it signs in with
            'routersIpAddresses' => (string)env('DEBTORS_ROUTERS_IP_ADDRESSES', ''),
            'routersUsername' => (string)env('DEBTORS_ROUTERS_USERNAME', 'admin'),
            'routersPassword' => (string)env('DEBTORS_ROUTERS_PASSWORD', ''),
        ],
        'eurofaktura' => [
            'username' => (string)env('EUROFAKTURA_USERNAME', ''),
            'secretKey' => (string)env('EUROFAKTURA_SECRET_KEY', ''),
            'token' => (string)env('EUROFAKTURA_TOKEN', ''),
            // a separate account for issuing keeps the writes off the sync's rate limit
            'invoicesUsername' => (string)env('EUROFAKTURA_INVOICES_USERNAME', env('EUROFAKTURA_USERNAME', '')),
            'invoicesSecretKey' => (string)env('EUROFAKTURA_INVOICES_SECRET_KEY', env('EUROFAKTURA_SECRET_KEY', '')),
            'invoicesToken' => (string)env('EUROFAKTURA_INVOICES_TOKEN', env('EUROFAKTURA_TOKEN', '')),
        ],
        'pohoda' => [
            'username' => (string)env('POHODA_USERNAME', ''),
            'password' => (string)env('POHODA_PASSWORD', ''),
        ],
    ],

    'Radius' => [
        // nothing named leaves an account's group alone
        'defaultUserGroup' => (string)env('RADIUS_DEFAULT_USER_GROUP', ''),
        'routerosUsername' => (string)env('ROUTEROS_USERNAME', 'admin'),
        'routerosPassword' => (string)env('ROUTEROS_PASSWORD', ''),
    ],

    /*
     * Email delivery profiles
     *
     * Delivery profiles allow you to predefine various properties about email
     * messages from your application and give the settings a name. This saves
     * duplication across your application and makes maintenance and development
     * easier. Each profile accepts a number of keys. See `Cake\Mailer\Mailer`
     * for more information.
     */
    'Email' => [
        'default' => [
            'transport' => 'default',
            'from' => 'you@localhost',
            /*
             * Will by default be set to config value of App.encoding, if that exists otherwise to UTF-8.
             */
            //'charset' => 'utf-8',
            //'headerCharset' => 'utf-8',
        ],
    ],

    /*
     * Connection information used by the ORM to connect
     * to your application's datastores.
     *
     * ### Notes
     * - Drivers include Mysql Postgres Sqlite Sqlserver
     *   See vendor\cakephp\cakephp\src\Database\Driver for the complete list
     * - Do not use periods in database name - it may lead to errors.
     *   See https://github.com/cakephp/cakephp/issues/6471 for details.
     * - 'encoding' is recommended to be set to full UTF-8 4-Byte support.
     *   E.g set it to 'utf8mb4' in MariaDB and MySQL and 'utf8' for any
     *   other RDBMS.
     */
    'Datasources' => [
        /*
         * These configurations should contain permanent settings used
         * by all environments.
         *
         * The values in app_local.php will override any values set here
         * and should be used for local and per-environment configurations.
         *
         * Environment variable-based configurations can be loaded here or
         * in app_local.php depending on the application's needs.
         */
        'default' => [
            'className' => Connection::class,
            'driver' => Postgres::class,
            'persistent' => false,
            'timezone' => 'UTC',

            /*
             * For MariaDB/MySQL the internal default changed from utf8 to utf8mb4, aka full utf-8 support
             */
            'encoding' => 'utf8',

            /*
             * If your MySQL server is configured with `skip-character-set-client-handshake`
             * then you MUST use the `flags` config to set your charset encoding.
             * For e.g. `'flags' => [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']`
             */
            'flags' => [],
            'cacheMetadata' => true,
            'log' => false,

            /*
             * Set identifier quoting to true if you are using reserved words or
             * special characters in your table or column names. Enabling this
             * setting will result in queries built using the Query Builder having
             * identifiers quoted when creating SQL. It should be noted that this
             * decreases performance because each query needs to be traversed and
             * manipulated before being executed.
             */
            'quoteIdentifiers' => false,

            /*
             * During development, if using MySQL < 5.6, uncommenting the
             * following line could boost the speed at which schema metadata is
             * fetched from the database. It can also be set directly with the
             * mysql configuration directive 'innodb_stats_on_metadata = 0'
             * which is the recommended value in production environments
             */
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
        'radius' => [
            'className' => Connection::class,
            'driver' => Postgres::class,
            'persistent' => false,
            'timezone' => 'UTC',

            /*
             * For MariaDB/MySQL the internal default changed from utf8 to utf8mb4, aka full utf-8 support
             */
            'encoding' => 'utf8',

            /*
             * If your MySQL server is configured with `skip-character-set-client-handshake`
             * then you MUST use the `flags` config to set your charset encoding.
             * For e.g. `'flags' => [\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']`
             */
            'flags' => [],
            'cacheMetadata' => true,
            'log' => false,

            /*
             * Set identifier quoting to true if you are using reserved words or
             * special characters in your table or column names. Enabling this
             * setting will result in queries built using the Query Builder having
             * identifiers quoted when creating SQL. It should be noted that this
             * decreases performance because each query needs to be traversed and
             * manipulated before being executed.
             */
            'quoteIdentifiers' => false,

            /*
             * During development, if using MySQL < 5.6, uncommenting the
             * following line could boost the speed at which schema metadata is
             * fetched from the database. It can also be set directly with the
             * mysql configuration directive 'innodb_stats_on_metadata = 0'
             * which is the recommended value in production environments
             */
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],

        /*
         * The test connection is used during the test suite.
         */
        'test' => [
            'className' => Connection::class,
            'driver' => Postgres::class,
            'persistent' => false,
            'timezone' => 'UTC',
            'encoding' => 'utf8',
            'flags' => [],
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
        'test_radius' => [
            'className' => Connection::class,
            'driver' => Postgres::class,
            'persistent' => false,
            'timezone' => 'UTC',
            'encoding' => 'utf8',
            'flags' => [],
            'cacheMetadata' => true,
            'quoteIdentifiers' => false,
            'log' => false,
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
        ],
    ],

    /*
     * Persisting audit log configuration
     */
    'AuditStash' => [
        'persister' => 'AuditStash\Persister\TablePersister',
        'blacklist' => ['created', 'created_by', 'modified', 'modified_by'],
        'adminAccess' => fn() => true, // enable UI access, use Cake DC Users for authentication and authorization
    ],

    /*
     * Configures logging options
     */
    'Log' => [
        'debug' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'debug',
            'url' => env('LOG_DEBUG_URL', null),
            'scopes' => null,
            'levels' => ['notice', 'info', 'debug'],
        ],
        'error' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'error',
            'url' => env('LOG_ERROR_URL', null),
            'scopes' => null,
            'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
        // To enable this dedicated query log, you need to set your datasource's log flag to true
        'queries' => [
            'className' => FileLog::class,
            'path' => LOGS,
            'file' => 'queries',
            'url' => env('LOG_QUERIES_URL', null),
            'scopes' => ['cake.database.queries'],
        ],
    ],

    /*
     * Session configuration.
     *
     * Contains an array of settings to use for session configuration. The
     * `defaults` key is used to define a default preset to use for sessions, any
     * settings declared here will override the settings of the default config.
     *
     * ## Options
     *
     * - `cookie` - The name of the cookie to use. Defaults to value set for `session.name` php.ini config.
     *    Avoid using `.` in cookie names, as PHP will drop sessions from cookies with `.` in the name.
     * - `cookiePath` - The url path for which session cookie is set. Maps to the
     *   `session.cookie_path` php.ini config. Defaults to base path of app.
     * - `timeout` - The time in minutes a session can be 'idle'. If no request is received in
     *    this duration, the session will be expired and rotated. Pass 0 to disable idle timeout checks.
     * - `defaults` - The default configuration set to use as a basis for your session.
     *    There are four built-in options: php, cake, cache, database.
     * - `handler` - Can be used to enable a custom session handler. Expects an
     *    array with at least the `engine` key, being the name of the Session engine
     *    class to use for managing the session. CakePHP bundles the `CacheSession`
     *    and `DatabaseSession` engines.
     * - `ini` - An associative array of additional 'session.*` ini values to set.
     *
     * Within the `ini` key, you will likely want to define:
     *
     * - `session.cookie_lifetime` - The number of seconds that cookies are valid for. This
     *    should be longer than `Session.timeout`.
     * - `session.gc_maxlifetime` - The number of seconds after which a session is considered 'garbage'
     *    that can be deleted by PHP's session cleanup behavior. This value should be greater than both
     *    `Sesssion.timeout` and `session.cookie_lifetime`.
     *
     * The built-in `defaults` options are:
     *
     * - 'php' - Uses settings defined in your php.ini.
     * - 'cake' - Saves session files in CakePHP's /tmp directory.
     * - 'database' - Uses CakePHP's database sessions.
     * - 'cache' - Use the Cache class to save sessions.
     *
     * To define a custom session handler, save it at src/Http/Session/<name>.php.
     * Make sure the class implements PHP's `SessionHandlerInterface` and set
     * Session.handler to <name>
     *
     * To use database sessions, load the SQL file located at config/schema/sessions.sql
     */
    'Session' => [
        'defaults' => 'database',
        'timeout' => 1440,
    ],

    /**
     * DebugKit configuration.
     *
     * Contains an array of configurations to apply to the DebugKit plugin, if loaded.
     * Documentation: https://book.cakephp.org/debugkit/5/en/index.html#configuration
     *
     * ## Options
     *
     *  - `panels` - Enable or disable panels. The key is the panel name, and the value is true to enable,
     *     or false to disable.
     *  - `includeSchemaReflection` - Set to true to enable logging of schema reflection queries. Disabled by default.
     *  - `safeTld` - Set an array of whitelisted TLDs for local development.
     *  - `forceEnable` - Force DebugKit to display. Careful with this, it is usually safer to simply whitelist
     *     your local TLDs.
     *  - `ignorePathsPattern` - Regex pattern (including delimiter) to ignore paths.
     *     DebugKit won’t save data for request URLs that match this regex.
     *  - `ignoreAuthorization` - Set to true to ignore Cake Authorization plugin for DebugKit requests.
     *     Disabled by default.
     *  - `maxDepth` - Defines how many levels of nested data should be shown in general for debug output.
     *     Default is 5. WARNING: Increasing the max depth level can lead to an out of memory error.
     *  - `variablesPanelMaxDepth` - Defines how many levels of nested data should be shown in the variables tab.
     *     Default is 5. WARNING: Increasing the max depth level can lead to an out of memory error.
     */
    'DebugKit' => [
        'forceEnable' => filter_var(env('DEBUG_KIT_FORCE_ENABLE', false), FILTER_VALIDATE_BOOLEAN),
        'safeTld' => env('DEBUG_KIT_SAFE_TLD', null),
        'ignoreAuthorization' => env('DEBUG_KIT_IGNORE_AUTHORIZATION', false),
    ],

    /**
     * TestSuite configuration.
     *
     * ## Options
     *
     *  - `errorLevel` - Defaults to `E_ALL`. Can be set to `false` to disable overwrite error level.
     *  - `fixtureStrategy` - Defaults to TruncateStrategy. Can be set to any class implementing FixtureStrategyInterface.
     */
    'TestSuite' => [
        'errorLevel' => null,
        'fixtureStrategy' => null,
    ],

    /*
     * What the pages offer. The rest of `UI` is written per request from the user's own
     * settings; this is only what the installation decides once.
     */
    'UI' => [
        'select2' => filter_var(env('ENABLE_SELECT2', false), FILTER_VALIDATE_BOOLEAN),
    ],

    /*
     * Database Migrations
     */
    'Migrations' => [
        'backend' => 'builtin',
        'unsigned_primary_keys' => true,
        'column_null_default' => true,
    ],

    /*
     * Historical Connections
     *
     * Systems asked where accounts have been connected over time. Each entry is
     * a class implementing \App\Service\HistoricalConnections\SourceInterface. They
     * are only ever read from, so a source may be removed here without any of
     * the history recorded from it being affected.
     */
    'HistoricalConnections' => [
        'sources' => [
            RadiusSource::class,
        ],
    ],

    /*
     * Business Register
     *
     * The registers a company may be looked up in. Each entry is a class implementing
     * \App\BusinessRegister\SourceInterface, keyed by the name its settings live under.
     * Whether a register is offered is decided in the settings, not here.
     */
    'BusinessRegister' => [
        'sources' => [
            'ares' => AresSource::class,
            'sudreg' => SudregSource::class,
            'vies' => ViesSource::class,
        ],
    ],
];
