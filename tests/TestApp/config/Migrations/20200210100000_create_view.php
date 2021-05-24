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

use CakephpTestMigrator\Test\TestCase\SchemaCleanerListTablesTest;
use Migrations\AbstractMigration;

class CreateView extends AbstractMigration
{
    public function up()
    {
        try {
            $this->execute(
                "CREATE VIEW " . SchemaCleanerListTablesTest::VIEW_NAME . " AS SELECT '" . SchemaCleanerListTablesTest::VIEW_CONTENT . "'"
            );
        } catch (Throwable $e) {
            // Do nothing, the view might already exist.
        }
    }
}
