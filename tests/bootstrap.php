<?php

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__));
define('TESTS', ROOT . DS . 'tests' . DS);
define('APP_PATH', TESTS . 'TestApp' . DS);
define('CONFIG', APP_PATH . 'config' . DS);
define('VENDOR_PATH', ROOT . DS . 'vendor' . DS);
define('CAKE_CORE_INCLUDE_PATH', VENDOR_PATH . 'cakephp' . DS . 'cakephp');

$loadEnv = function(string $fileName) {
    if (file_exists($fileName)) {
        $dotenv = new \josegonzalez\Dotenv\Loader($fileName);
        $dotenv->parse()
            ->putenv(true)
            ->toEnv(true)
            ->toServer(true);
    }
};

if (!getenv('DB_DRIVER')) {
    putenv('DB_DRIVER=Sqlite');
}
$driver =  getenv('DB_DRIVER');

if (!file_exists(ROOT . DS . '.env')) {
    @copy(".env.$driver", ROOT . DS . '.env');
}

/**
 * Read .env file(s).
 */
$loadEnv(ROOT . DS . '.env');

// Re-read the driver
$driver =  getenv('DB_DRIVER');
echo "Using driver $driver \n";

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'TestApp',
    'paths' => [
        'plugins' => [TESTS . 'Plugins' . DS],
    ],
]);

if (!function_exists('loadPHPUnitAliases')) {
    /**
     * Loads PHPUnit aliases
     *
     * This is an internal function used for backwards compatibility during
     * fixture related tests.
     *
     * @return void
     */
    function loadPHPUnitAliases()
    {
        require_once CAKE_CORE_INCLUDE_PATH . DS . 'tests' . DS . 'phpunit_aliases.php';
    }
}

$dbConnection = [
    'className' => 'Cake\Database\Connection',
    'driver' => 'Cake\Database\Driver\\' . $driver,
    'persistent' => false,
    'host' => getenv('DB_HOST'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PWD'),
    'database' => getenv('DB_DATABASE'),
    'encoding' => 'utf8',
    'timezone' => 'UTC',
    'cacheMetadata' => true,
    'quoteIdentifiers' => true,
    'log' => false,
    //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],
    'url' => env('DATABASE_TEST_URL', null),
    'migrations' => [
        ['source' => 'FooSource'],
        ['plugin' => 'FooPlugin'],
    ],
];

ConnectionManager::setConfig('test', $dbConnection);

$dbDefaultConnection = $dbConnection;
$dbDefaultConnection['database'] = 'migrator';
ConnectionManager::setConfig('default', $dbConnection);

$dbConnection['migrations'] = ['plugin' => 'BarPlugin'];
ConnectionManager::setConfig('test_2', $dbConnection);

$dbConnection['migrations'] = true;
ConnectionManager::setConfig('test_3', $dbConnection);

\Cake\Core\Plugin::load('FooPlugin');
\Cake\Core\Plugin::load('BarPlugin');
