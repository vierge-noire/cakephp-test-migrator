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


use Cake\Database\Exception\DatabaseException;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakephpTestMigrator\ConfigReader;
use CakephpTestMigrator\Migrator;
use CakephpTestMigrator\TestConnectionManager;
use CakephpTestMigrator\TestSchemaCleaner;
use MigratorTestApp\Model\Table\ArticlesTable;

class TestConnectionManagerTest extends TestCase
{
    /**
     * Note that phinxlog tables are suffixed by _phinxlog.
     */
    public function testUnsetMigrationTables(): void
    {
        $input = ['foo', 'phinxlog', 'phinxlog_bar', 'some_table', 'some_plugin_phinxlog'];
        $output = TestConnectionManager::unsetMigrationTables($input);
        $this->assertSame(['foo', 'phinxlog_bar', 'some_table',], $output);
    }

    public function testAliasConnections(): void
    {
        TestConnectionManager::aliasConnections();
        $testDB = ConnectionManager::get('default')->config()['database'];
        $this->assertSame('test_migrator', $testDB);
    }
}
