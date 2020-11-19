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


use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use CakephpTestMigrator\ConfigReader;
use CakephpTestSuiteLight\FixtureManager;

class ConfigReaderTest extends TestCase
{
    /**
     * @var ConfigReader
     */
    public $ConfigReader;

    public function setUp()
    {
        $this->ConfigReader = new ConfigReader();
    }

    public function tearDown()
    {
        unset($this->ConfigReader);
    }

    public function testSetConfigFromInjection()
    {
        $config = [
            'connection' => 'Foo',
            'plugin' => 'Bar'
        ];

        $this->ConfigReader->loadConfig($config);

        $this->assertSame([$config], $this->ConfigReader->getConfig());
    }

    public function testSetConfigWithConfigure()
    {
        $config = [
            'connection' => 'FooTestSetConfigWithConfigure',
            'plugin' => 'BarTestSetConfigWithConfigure'
        ];

        Configure::write('TestFixtureMigrations', $config);

        $this->ConfigReader->loadConfig();

        $this->assertSame([$config], $this->ConfigReader->getConfig());

        Configure::delete('TestFixtureMigrations');
    }

    public function testSetConfigWithConfigureAndInjection()
    {
        $config1 = [
            'connection' => 'Foo1_testSetConfigWithConfigureAndInjection',
            'plugin' => 'Bar1_testSetConfigWithConfigureAndInjection'
        ];

        $config2 = [
            'connection' => 'Foo2_testSetConfigWithConfigureAndInjection',
            'plugin' => 'Bar2_testSetConfigWithConfigureAndInjection'
        ];

        Configure::write('TestFixtureMigrations', $config2);

        $this->ConfigReader->loadConfig($config1);
        $this->assertSame([$config1], $this->ConfigReader->getConfig());

        Configure::delete('TestFixtureMigrations');
    }

    public function testReadMigrationsInDatasource()
    {
        $fm = new FixtureManager();
        $this->ConfigReader->readMigrationsInDatasources($fm);
        $act = $this->ConfigReader->getConfig();
        $expected = [
            ['source' => 'FooSource', 'connection' => 'test'],
            ['plugin' => 'FooPlugin', 'connection' => 'test'],
            ['plugin' => 'BarPlugin', 'connection' => 'test_2'],
            ['connection' => 'test_3'],
        ];
        $this->assertSame($expected, $act);
    }

    public function arrays()
    {
        return [
            [['a' => 'b'], [['a' => 'b']]],
            [[['a' => 'b']], [['a' => 'b']]],
            [[], []],
        ];
    }

    /**
     * @dataProvider arrays
     */
    public function testNormalizeArray($input, $expect)
    {
        $this->ConfigReader->normalizeArray($input);
        $this->assertSame($expect, $input);
    }
}