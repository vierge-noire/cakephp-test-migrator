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
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Hash;

class SchemaCleaner
{
    /**
     * @var \Cake\Console\ConsoleIo|null
     */
    protected $io;

    /**
     * SchemaCleaner constructor.
     *
     * @param \Cake\Console\ConsoleIo|null $io Outputs if provided.
     */
    public function __construct(?ConsoleIo $io = null)
    {
        $this->io = $io;
    }

    /**
     * Drop all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @return void
     * @throws \Exception if the dropping failed.
     */
    public function drop(string $connectionName)
    {
        $this->info("Dropping all tables for connection {$connectionName}.");

        $connection = ConnectionManager::get($connectionName);
        $stmts = [];
        foreach ($this->listTables($connection) as $table) {
            $table = $connection->getSchemaCollection()->describe($table);
            if ($table instanceof TableSchema) {
                $driver = $connection->getDriver();
                $stmts = array_merge($stmts, $driver->schemaDialect()->dropTableSql($table));
            }
        }

        $this->executeStatements(ConnectionManager::get($connectionName), $stmts);
    }

    /**
     * Truncate all tables of the provided connection.
     *
     * @param string $connectionName Name of the connection.
     * @return void
     * @throws \Exception if the truncation failed.
     */
    public function truncate(string $connectionName)
    {
        $this->info("Truncating all tables for connection {$connectionName}.");

        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get($connectionName);
        $stmts = [];
        $tables = $this->listTables($connection);
        $tables = $this->unsetMigrationTables($tables);
        foreach ($tables as $table) {
            $table = $connection->getSchemaCollection()->describe($table);
            if ($table instanceof TableSchema) {
                $driver = $connection->getDriver();
                $stmts = array_merge($stmts, $driver->schemaDialect()->truncateTableSql($table));
            }
        }

        $this->executeStatements($connection, $stmts);
    }

    /**
     * List sall tables, without views.
     *
     * @param ConnectionInterface $connection Connection.
     * @return array
     */
    public function listTables(ConnectionInterface $connection): array
    {
        $driver = $connection->getDriver();
        if ($driver instanceof Mysql) {
            $query = 'SHOW FULL TABLES WHERE Table_Type != "VIEW"';
        } elseif ($driver instanceof Postgres) {
            $query = "SELECT tablename AS TABLE FROM pg_tables WHERE schemaname = 'public'";
        } else {
            return $connection->getSchemaCollection()->listTables();
        }

        $result = $connection->execute($query)->fetchAll();
        if ($result === false) {
            throw new \PDOException($query . ' failed');
        }

        return (array)Hash::extract($result, '{n}.0');
    }

    /**
     * @param  \Cake\Datasource\ConnectionInterface $connection Connection.
     * @param  array               $commands Sql commands to run
     * @return void
     * @throws \Exception
     */
    protected function executeStatements(ConnectionInterface $connection, array $commands): void
    {
        $connection->disableConstraints(function (ConnectionInterface $connection) use ($commands) {
            $connection->transactional(function (ConnectionInterface $connection) use ($commands) {
                foreach ($commands as $sql) {
                    $connection->execute($sql);
                }
            });
        });
    }

    /**
     * @param string $msg Message to display.
     * @return void
     */
    protected function info(string $msg): void
    {
        if ($this->io instanceof ConsoleIo) {
            $this->io->info($msg);
        }
    }

    /**
     * Unset the phinx migration tables from an array of tables.
     *
     * @param  string[] $tables
     * @return array
     */
    public function unsetMigrationTables(array $tables): array
    {
        $endsWithPhinxlog = function (string $string) {
            $needle = 'phinxlog';
            return substr($string, -strlen($needle)) === $needle;
        };

        foreach ($tables as $i => $table) {
            if ($endsWithPhinxlog($table)) {
                unset($tables[$i]);
            }
        }

        return array_values($tables);
    }
}
