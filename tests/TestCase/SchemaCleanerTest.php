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

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use CakephpTestMigrator\SchemaCleaner;
use Exception;
use Migrations\Migrations;

class SchemaCleanerTest extends TestCase
{
    /**
     * @var ArticlesTable
     */
    public $Articles;

    public function setUp(): void
    {
        $this->Articles = TableRegistry::getTableLocator()->get('Articles');
    }

    public function tearDown(): void
    {
        unset($this->Articles);
    }

    public function testDropSchema(): void
    {
        // Drop tables to ensure that the following migration runs
        (new SchemaCleaner())->drop('test');

        // Populate the schema
        $migrations = new Migrations();
        $migrations->migrate(['connection' => 'test']);
        $this->assertSame(1, $this->Articles->find()->count());

        // Drop the schema
        (new SchemaCleaner())->drop('test');

        $this->expectException(Exception::class);
        $this->Articles->find()->all();
    }

    public function testTruncateSchema(): void
    {
        // Drop tables to ensure that the following migration runs
        (new SchemaCleaner())->drop('test');
        // Populate the schema
        $migrations = new Migrations(['connection' => 'test']);
        $migrations->migrate();
        $this->assertSame(1, $this->Articles->find()->count());

        // Truncate the schema
        (new SchemaCleaner())->truncate('test');
        $this->assertSame(0, $this->Articles->find()->count());

        $migration = $migrations->status()[0];
        $this->assertSame('up', $migration['status']);
    }
}
