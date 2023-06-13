<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use josegonzalez\Dotenv\Loader;
use function Cake\Core\env;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT', dirname(__DIR__));
define('TESTS', ROOT . DS . 'tests' . DS);
define('APP_PATH', TESTS . 'TestApp' . DS);
define('CONFIG', APP_PATH . 'config' . DS);
define('TMP', ROOT . DS . 'tmp' . DS);
define('CACHE', TMP . 'cache' . DS);

$loadEnv = function (string $fileName) {
    if (file_exists($fileName)) {
        $dotenv = new Loader($fileName);
        $dotenv->parse()
            ->putenv(true)
            ->toEnv(true)
            ->toServer(true);
    }
};

if (!getenv('DB_DRIVER')) {
    putenv('DB_DRIVER=Sqlite');
}
$driver = getenv('DB_DRIVER');

if (file_exists(TESTS . ".env.$driver") && !file_exists(TESTS . '.env')) {
    copy(TESTS . ".env.$driver", TESTS . '.env');
}

/**
 * Read .env file(s).
 */
$loadEnv(TESTS . '.env');

// Re-read the driver
$driver = getenv('DB_DRIVER');
echo "Using driver $driver \n";

Configure::write('debug', true);
Configure::write(
    'App',
    [
    'namespace' => 'TestApp',
    'paths' => [
        'plugins' => [TESTS . 'Plugins' . DS],
    ],
    ]
);

$cacheConfig = [
    'className' => 'File',
    'path' => CACHE,
    'url' => env('CACHE_DEFAULT_URL', null),
    'duration' => '+2 minutes',
];

Cache::setConfig('_cake_model_', $cacheConfig);
Cache::setConfig('_cake_core_', $cacheConfig);

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
