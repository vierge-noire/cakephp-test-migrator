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


use Cake\TestSuite\TestCase;
use CakephpTestMigrator\Migrator;
use CakephpTestSuiteLight\FixtureManager;
use CakephpTestSuiteLight\Sniffer\BaseTableSniffer;

class MigratorTest extends TestCase
{
    /**
     * @var Migrator
     */
    public $migrator;

    /**
     * @var BaseTableSniffer
     */
    public $sniffer;

    public function setUp()
    {
        $this->migrator = new Migrator();

        $fm = new FixtureManager();
        $this->sniffer = $fm->getSniffer('test');
    }

    public function tearDown()
    {
        unset($this->migrator);
        unset($this->sniffer);
    }

    private function fetchMigrationsInDB(string $dbTable): array
    {
        return $this->sniffer->fetchQuery("SELECT migration_name FROM $dbTable");
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
}