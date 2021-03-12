<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link          https://webrider.de/
 * @since         1.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CakephpTestMigrator\Test\TestCase;


use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakephpTestMigrator\Migrator;

class MigratorTest extends TestCase
{
    /**
     * @var Migrator
     */
    public $migrator;

    public function setUp()
    {
        $this->migrator = new Migrator();
    }

    public function tearDown()
    {
        unset($this->migrator);
    }

    private function fetchMigrationsInDB(string $dbTable): array
    {
        return ConnectionManager::get('test')->execute("SELECT migration_name FROM $dbTable")->fetch();
    }

    public function testGetConfig()
    {
        $expect = [
            ['source' => 'FooSource', 'connection' => 'test'],
            ['plugin' => 'FooPlugin', 'connection' => 'test'],
            ['plugin' => 'BarPlugin', 'connection' => 'test_2'],
            ['connection' => 'test_3'],
        ];
        $config = $this->migrator->getConfig();
        $this->assertSame($expect, $config);
    }

    public function testMigrate()
    {
        Migrator::migrate();

        $appMigrations = $this->fetchMigrationsInDB('phinxlog');
        $fooPluginMigrations = $this->fetchMigrationsInDB('foo_plugin_phinxlog');
        $barPluginMigrations = $this->fetchMigrationsInDB('bar_plugin_phinxlog');

        $this->assertSame(['AppMigration'], $appMigrations);
        $this->assertSame(['FooMigration'], $fooPluginMigrations);
        $this->assertSame(['BarMigration'], $barPluginMigrations);
    }

    public function testTableRegistryConnectionName()
    {
        $Articles = TableRegistry::getTableLocator()->get('Articles');
        ConnectionManager::getConfigOrFail('default');
        $this->assertSame('test', $Articles->getConnection()->configName());
    }
}
