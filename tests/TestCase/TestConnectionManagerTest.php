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
use CakephpTestMigrator\TestConnectionManager;

class TestConnectionManagerTest extends TestCase
{
    public function testAliasConnections()
    {
        TestConnectionManager::aliasConnections();
        $testDB = ConnectionManager::get('default')->config()['database'];
        $this->assertSame('test_migrator', $testDB);
    }
}
