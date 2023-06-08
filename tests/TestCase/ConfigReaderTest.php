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

use Cake\TestSuite\TestCase;
use CakephpTestMigrator\ConfigReader;

class ConfigReaderTest extends TestCase
{
    /**
     * @var ConfigReader
     */
    public $ConfigReader;

    public function setUp(): void
    {
        $this->ConfigReader = new ConfigReader();
    }

    public function tearDown(): void
    {
        unset($this->ConfigReader);
    }

    public function testSetConfigFromInjection(): void
    {
        $config = [
            ['connection' => 'Foo', 'plugin' => 'Bar',],
            ['plugin' => 'Bar',],
        ];

        $expect = [
            ['connection' => 'Foo', 'plugin' => 'Bar',],
            ['plugin' => 'Bar', 'connection' => 'test',],
        ];

        $this->ConfigReader->readConfig($config);

        $this->assertSame($expect, $this->ConfigReader->getConfig());
    }

    public function testSetConfigFromEmptyInjection(): void
    {
        $expect = [
            ['connection' => 'test'],
        ];

        $this->ConfigReader->readConfig();

        $this->assertSame($expect, $this->ConfigReader->getConfig());
    }

    public function testSetConfigWithConfigureAndInjection(): void
    {
        $config1 = [
            'connection' => 'Foo1_testSetConfigWithConfigureAndInjection',
            'plugin' => 'Bar1_testSetConfigWithConfigureAndInjection',
        ];

        $this->ConfigReader->readConfig($config1);
        $this->assertSame([$config1], $this->ConfigReader->getConfig());
    }

    public function testReadMigrationsInDatasource(): void
    {
        $this->ConfigReader->readMigrationsInDatasources();
        // Read empty config will not overwrite Datasource config
        $this->ConfigReader->readConfig();
        $act = $this->ConfigReader->getConfig();
        $expected = [
            ['source' => 'FooSource', 'connection' => 'test'],
            ['plugin' => 'FooPlugin', 'connection' => 'test'],
            ['plugin' => 'BarPlugin', 'connection' => 'test_2'],
            ['connection' => 'test_3'],
        ];
        $this->assertSame($expected, $act);
    }

    public function testReadMigrationsInDatasourceAndInjection(): void
    {
        $this->ConfigReader->readMigrationsInDatasources();
        // Read non-empty config will overwrite Datasource config
        $this->ConfigReader->readConfig(['source' => 'Foo']);
        $act = $this->ConfigReader->getConfig();
        $expected = [
            ['source' => 'Foo', 'connection' => 'test'],
        ];
        $this->assertSame($expected, $act);
    }

    public static function arrays(): array
    {
        return [
            [['a' => 'b'], [['a' => 'b']]],
            [[['a' => 'b']], [['a' => 'b']]],
            [[], []],
        ];
    }

    /**
     * @dataProvider arrays
     * @param        array $input
     * @param        array $expect
     */
    public function testNormalizeArray(array $input, array $expect): void
    {
        $this->ConfigReader->normalizeArray($input);
        $this->assertSame($expect, $input);
    }
}
