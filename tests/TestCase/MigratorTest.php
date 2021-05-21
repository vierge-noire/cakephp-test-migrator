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

    public function testGetConfigFromDatasource(): void
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

    public function testMigrate(): void
    {
        Migrator::migrate();

        $appMigrations = $this->fetchMigrationsInDB('phinxlog');
        $fooPluginMigrations = $this->fetchMigrationsInDB('foo_plugin_phinxlog');
        $barPluginMigrations = $this->fetchMigrationsInDB('bar_plugin_phinxlog');

        $this->assertSame(['AddArticles', 'PopulateArticles', 'CreateView'], $appMigrations);
        $this->assertSame(['FooMigration'], $fooPluginMigrations);
        $this->assertSame(['BarMigration'], $barPluginMigrations);
    }

    public function testTableRegistryConnectionName(): void
    {
        $Articles = TableRegistry::getTableLocator()->get('Articles');
        ConnectionManager::getConfigOrFail('default');
        $this->assertSame('test', $Articles->getConnection()->configName());
    }

    public function testDropTablesForMissingMigrations(): void
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
