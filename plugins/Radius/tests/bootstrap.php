<?php
declare(strict_types=1);

/**
 * Test suite bootstrap for RADIUS.
 *
 * This function is used to find the location of CakePHP whether CakePHP
 * has been installed as a dependency of the plugin, or the plugin is itself
 * installed as a dependency of an application.
 */
$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);

    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);

chdir($root);

require_once $root . '/vendor/autoload.php';

/**
 * Load application bootstrap if possible
 */
if (file_exists($root . '/config/bootstrap.php')) {
    require_once $root . '/config/bootstrap.php';

    #return;
} else {
    /**
     * Define fallback values for required constants and configuration.
     * To customize constants and configuration remove this require
     * and define the data required by your plugin here.
     */
    require_once $root . '/vendor/cakephp/cakephp/tests/bootstrap.php';
}

/**
 * Load schema from a SQL dump file.
 *
 * If your plugin does not use database fixtures you can
 * safely delete this.
 *
 * If you want to support multiple databases, consider
 * using migrations to provide schema for your plugin,
 * and using \Migrations\TestSuite\Migrator to load schema.
 */
use Cake\TestSuite\Fixture\SchemaLoader;

// Load a schema dump file.
(new SchemaLoader())->loadSqlFiles(
    [
        dirname(__DIR__) . '/tests/schema.sql',
        dirname(__DIR__) . '/config/ManualMigrations/1001_InitialMaster.sql',
        dirname(__DIR__) . '/config/ManualMigrations/2001_PreMigrateRelatedKeysToUuidOnAccounts.sql',
        dirname(__DIR__) . '/config/ManualMigrations/2002_PostMigrateRelatedKeysToUuidOnAccounts.sql',
    ],
    'test'
);
