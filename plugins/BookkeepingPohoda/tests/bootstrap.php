<?php
declare(strict_types=1);

use Migrations\TestSuite\Migrator;

/**
 * Test suite bootstrap for BookkeepingPohoda.
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
    ['plugin' => 'BookkeepingPohoda'],
]);
