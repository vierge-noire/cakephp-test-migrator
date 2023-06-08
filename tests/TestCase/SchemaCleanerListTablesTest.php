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
use Cake\TestSuite\TestCase;
use CakephpTestMigrator\SchemaCleaner;

class SchemaCleanerListTablesTest extends TestCase
{
    public const VIEW_NAME = 'test_view';
    public const VIEW_CONTENT = 'Some dummy view on which no triggers should be created.';

    /**
     * @var SchemaCleaner
     */
    public $SchemaCleaner;

    public function setUp(): void
    {
        $this->SchemaCleaner = new SchemaCleaner();
    }

    public function tearDown(): void
    {
        unset($this->SchemaCleaner);
        parent::tearDown();
    }

    /**
     * Check that the view has been created
     */
    public function testThatTheViewExists()
    {
        $connection = ConnectionManager::get('test');
        $result = $connection->execute('SELECT * FROM ' . self::VIEW_NAME)->fetch()[0];
        $this->assertSame(self::VIEW_CONTENT, $result);
    }

    /**
     * Check that the view is ignored
     */
    public function testThatTheViewIsIgnored()
    {
        $connection = ConnectionManager::get('test');
        $allTables = $this->SchemaCleaner->listTables($connection);
        $this->assertSame(false, in_array(self::VIEW_NAME, $allTables));
    }
}
