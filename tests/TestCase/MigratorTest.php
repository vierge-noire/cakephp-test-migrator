<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link      https://webrider.de/
 * @since     1.0.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CakephpTestMigrator\Test\TestCase;


use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use CakephpTestMigrator\Migrator;
use CakephpTestMigrator\SchemaCleaner;

class MigratorTest extends TestCase
{
    private function fetchMigrationsInDB(string $dbTable): array
    {
        $result = ConnectionManager::get('test')
            ->newQuery()
            ->select('migration_name')
            ->from($dbTable)
            ->execute()
            ->fetchAll();

        return (array)Hash::extract($result, '{n}.0');
    }

    public function testGetConfigFromDatasource()
    {
        $expect = [
            ['source' => 'FooSource', 'connection' => 'test'],
            ['plugin' => 'FooPlugin', 'connection' => 'test'],
            ['plugin' => 'BarPlugin', 'connection' => 'test_2'],
            ['connection' => 'test_3'],
        ];
        $migrator = Migrator::migrate();
        $config = $migrator->getConfigs();
        $this->assertSame($expect, $config);
    }

    public function testMigrate()
    {
        Migrator::migrate();

        $appMigrations = $this->fetchMigrationsInDB('phinxlog');
        $fooPluginMigrations = $this->fetchMigrationsInDB('foo_plugin_phinxlog');
        $barPluginMigrations = $this->fetchMigrationsInDB('bar_plugin_phinxlog');

        $this->assertSame(['AddArticles', 'PopulateArticles', 'CreateView'], $appMigrations);
        $this->assertSame(['FooMigration'], $fooPluginMigrations);
        $this->assertSame(['BarMigration'], $barPluginMigrations);
    }

    public function testGetConnectionsWithModifiedStatus()
    {
        $connections = [
            'test',
            'test_2',
            'test_3',
        ];

        // Run the migrations once to make sure that all are up to date.
        Migrator::migrate();

        // Status did not changed.
        $migrator = Migrator::migrate();
        $this->assertSame([], $migrator->getConnectionsWithModifiedStatus());

        // Drop all connections' tables. Statuses are reset.
        $cleaner = new SchemaCleaner();
        foreach ($connections as $connection) {
            $cleaner->drop($connection);
        }

        // All connections were touched by the migrations.
        $migrator = Migrator::migrate();
        $connectionsWithModifiedStatus = $migrator->getConnectionsWithModifiedStatus();
        $this->assertSame($connections, $connectionsWithModifiedStatus);
    }

    public function testTableRegistryConnectionName()
    {
        $Articles = TableRegistry::getTableLocator()->get('Articles');
        ConnectionManager::getConfig('default');
        $this->assertSame('test', $Articles->getConnection()->configName());
    }

    public function testDropTablesForMissingMigrations()
    {
        $connection = ConnectionManager::get('test');
        $connection->insert('phinxlog', ['version' => 1, 'migration_name' => 'foo',]);

        $count = $connection->newQuery()->select('version')->from('phinxlog')->execute()->count();
        $this->assertSame(4, $count);

        Migrator::migrate();
        $count = $connection->newQuery()->select('version')->from('phinxlog')->execute()->count();
        $this->assertSame(3, $count);
    }
}
