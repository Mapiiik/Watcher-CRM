<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\I18n;
use Cake\TestSuite\ConnectionHelper;
use Cake\TestSuite\Fixture\SchemaLoader;
use Migrations\TestSuite\Migrator;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/config/bootstrap.php';

if (empty($_SERVER['HTTP_HOST'])) {
    Configure::write('App.fullBaseUrl', 'http://localhost');
}

// DebugKit skips settings these connection config if PHP SAPI is CLI / PHPDBG.
// But since PagesControllerTest is run with debug enabled and DebugKit is loaded
// in application, without setting up these config DebugKit errors out.
ConnectionManager::setConfig('test_debug_kit', [
    'className' => Connection::class,
    'driver' => Sqlite::class,
    'database' => TMP . 'debug_kit.sqlite',
    'encoding' => 'utf8',
    'cacheMetadata' => true,
    'quoteIdentifiers' => false,
]);

ConnectionManager::alias('test_debug_kit', 'debug_kit');

// Fixate now to avoid one-second-leap-issues
Chronos::setTestNow(Chronos::now());

// Fixate the language, so that what a test reads back is what the source says.
// The application runs in Czech, and a test asserting on a message would then be
// asserting on a translation - it would go red the day somebody corrected the
// wording in Poedit, which has nothing to do with whether the code still works.
//
// The default has to go with it: a request picks its language per user and falls
// back to `I18n::getDefaultLocale()`, so a controller test would otherwise leave
// the suite in Czech for whatever ran after it. There is no setter for that one
// - it answers with the locale intl was left holding, once, and remembers it.
Locale::setDefault('en_US');
I18n::setLocale('en_US');

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Connection aliasing needs to happen before migrations are run.
// Otherwise, table objects inside migrations would use the default datasource
/** @psalm-suppress InternalMethod */
ConnectionHelper::addTestAliases();

// Use migrations to build test database schema.
//
// Will rebuild the database if the migration state differs
// from the migration history in files.
//
// If you are not using CakePHP's migrations you can
// hook into your migration tool of choice here or
// load schema from a SQL dump file with
// use Cake\TestSuite\SchemaLoader;
// (new SchemaManager())->loadSqlFiles('./tests/schema.sql', 'test');

$migrator = new Migrator();

// Run migrations on test connection
$migrator->runMany([
    ['plugin' => 'CakeDC/Users'],
    [],
    ['plugin' => 'Settings'],
    ['plugin' => 'Bookkeeping'],
]);

/*
 * Build the RADIUS schema on its own connection.
 *
 * The plugin's migrations only reshape `accounts`; the tables themselves belong to FreeRADIUS
 * and are created outside this project. What stands in for that here are the plugin's own
 * runbooks, applied in the order a primary database goes through them:
 *
 *   1001  the FreeRADIUS schema plus `accounts`
 *   2001  integer keys renamed aside, UUID columns added
 *   2002  UUID columns tightened, the old integer ones dropped
 *   2003  the primary key moved to UUID
 *
 * The two Cake migrations that normally sit between 2001 and 2002 only carry data across, and
 * there is none on a freshly built schema, so the SQL alone lands on the same shape. Running
 * the migrator here instead would be worse than redundant: it reports the migrations as `down`
 * on an empty database and drops every table it finds before applying them - including the
 * ones these files just created.
 *
 * The plugin's own cleanup file has to come first. SchemaLoader drops the tables it finds, but
 * the sequences of 1001 are standalone rather than owned by a column, so they outlive their
 * tables and the second run would fail on creating them again.
 */
$radiusRunbooks = ROOT . DS . 'plugins' . DS . 'Radius' . DS . 'config' . DS . 'ManualMigrations' . DS;

(new SchemaLoader())->loadSqlFiles(
    [
        ROOT . DS . 'plugins' . DS . 'Radius' . DS . 'tests' . DS . 'schema.sql',
        $radiusRunbooks . '1001_InitialMaster.sql',
        $radiusRunbooks . '2001_PreMigrateRelatedKeysToUuidOnAccounts.sql',
        $radiusRunbooks . '2002_PostMigrateRelatedKeysToUuidOnAccounts.sql',
        $radiusRunbooks . '2003_MigratePrimaryKeyToUuidOnAccounts.sql',
        $radiusRunbooks . '2004_AddUserStartIndexOnRadacct.sql',
    ],
    'test_radius',
);
