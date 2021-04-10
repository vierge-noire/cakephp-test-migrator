<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link      https://webrider.de/
 * @since     2.2.0
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CakephpTestMigrator;

use Cake\Console\ConsoleIo;
use Cake\Database\Schema\BaseSchema;
use Cake\Database\Schema\CollectionInterface;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;

class TestSchemaCleaner
{
    /**
     * Drop all tables of the provided connection.
     *
     * @param  string         $connectionName
     * @param  ConsoleIo|null $io
     * @return void
     */
    public static function dropSchema(string $connectionName, ?ConsoleIo $io = null): void
    {
        self::info($io, 'Dropping all tables for connection ' . $connectionName . '.');

        $schema = static::getSchema($connectionName);
        $dialect = static::getDialect($connectionName);

        $stmts = [];
        foreach ($schema->listTables() as $table) {
            $table = $schema->describe($table);
            $stmts = array_merge($stmts, $dialect->dropTableSql($table)); /** @phpstan-ignore-line */
        }

        static::executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * Truncate all tables of the provided connection.
     *
     * @param  string         $connectionName
     * @param  ConsoleIo|null $io
     * @return void
     */
    public static function truncateSchema(string $connectionName, ?ConsoleIo $io = null): void
    {
        static::info($io,'Truncating all tables for connection "' . $connectionName . '".');

        $stmts = [];
        $schema = static::getSchema($connectionName);
        $dialect = static::getDialect($connectionName);
        $tables = $schema->listTables();
        $tables = TestConnectionManager::unsetMigrationTables($tables);
        foreach ($tables as $table) {
            $table = $schema->describe($table);
            $stmts = array_merge($stmts, $dialect->truncateTableSql($table)); /** @phpstan-ignore-line */
        }

        static::executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * @param  ConnectionInterface $connection
     * @param  array               $commands
     * @throws \Exception
     */
    private static function executeStatements(ConnectionInterface $connection, array $commands): void
    {
        $connection->disableConstraints(function ($connection) use ($commands) {
            $connection->transactional(function (ConnectionInterface $connection) use ($commands) {
                foreach ($commands as $sql) {
                    $connection->execute($sql);
                }
            });
        });
    }

    /**
     * @param ConsoleIo|null $io
     * @param string         $msg
     */
    private static function info($io, string $msg): void
    {
        if ($io instanceof ConsoleIo) {
            $io->info($msg);
        }
    }

    /**
     * @param  string $connectionName
     * @return CollectionInterface
     */
    private static function getSchema(string $connectionName): CollectionInterface
    {
        return ConnectionManager::get($connectionName)->getSchemaCollection();
    }

    /**
     * @param  string $connectionName
     * @return BaseSchema
     */
    private static function getDialect(string $connectionName): BaseSchema
    {
        return ConnectionManager::get($connectionName)->getDriver()->schemaDialect(); /** @phpstan-ignore-line */
    }
}
