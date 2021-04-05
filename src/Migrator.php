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
namespace CakephpTestMigrator;


use Cake\Console\ConsoleIo;
use Cake\Datasource\ConnectionManager;
use Cake\Error\FatalErrorException;
use Migrations\Migrations;

class Migrator
{
    /**
     * @var ConfigReader
     */
    protected $configReader;

    /**
     * @var ConsoleIo
     */
    protected $io;

    final public function __construct(?bool $verbose = false, ?ConfigReader $configReader = null)
    {
        $this->io = new ConsoleIo();
        $this->io->level($verbose ? ConsoleIo::NORMAL : ConsoleIo::QUIET);
        $this->configReader = $configReader ?? new ConfigReader();

        // Make sure that the connections are aliased, in case
        // the migrations invoke the table registry.
        TestConnectionManager::aliasConnections();
    }

    /**
     * General command to run before your tests run
     * E.g. in tests/bootstrap.php
     *
     * @param array $config
     * @param bool  $verbose Set to true to display messages
     *
     * @return Migrator
     */
    public static function migrate(array $config = [], $verbose = false): Migrator
    {
        if (!class_exists(Migrations::class)) {
            throw new FatalErrorException(__d('cake', 'The Migration class could not be found.'));
        }

        $migrator = new static($verbose);

        $migrator->configReader->readMigrationsInDatasources();

        $migrator->configReader->readConfig($config);

        $migrator->dropTablesForMissingMigrations();
        $migrator->runAllMigrations();

        return $migrator;
    }

    /**
     * Import the schema from a file, or an array of files.
     *
     * @param  string          $connectionName Connection
     * @param  string|string[] $file           File to dump
     * @param  bool            $verbose        Set to true to display messages
     * @return void
     */
    public static function dump(string $connectionName, $file, bool $verbose): void
    {
        $files = (array)$file;

        $migrator = new static($verbose);

        $schemaCleaner = new TestSchemaCleaner();

        TestSchemaCleaner::dropSchema($connectionName, $migrator->io);

        foreach ($files as $file) {
            $sql = file_get_contents($file);

            if ($sql === false) {
                throw new \RuntimeException(__('cake', 'The file {0} could not be read.', $file));
            }

            $migrator->io->info(__d('cake', 'Dumping schema in file {0} for connection {1}.', [$file, $connectionName]));
            ConnectionManager::get($connectionName)->execute($sql);
            $migrator->io->success(__d('cake', 'Dump of schema in file {0} for connection {1} successful.', [$file, $connectionName]));
        }

        $schemaCleaner::truncateSchema($connectionName, $migrator->io);
    }

    /**
     * Run migrations for all configured migrations.
     *
     * @return void
     */
    protected function runAllMigrations(): void
    {
        foreach ($this->getConfigs() as $config) {
            $migrations = new Migrations();
            $result = $migrations->migrate($config);
            if ($result === true) {
                $this->io->success(__d('cake', 'Running for connection {0} successful.', $config['connection']));
            } else {
                $this->io->error(__d('cake', 'Migration for connection {0} failed.', $config['connection']));
            }

            TestSchemaCleaner::truncateSchema($config['connection'], $this->io);
        }
    }

    /**
     * If a migration is missing, all tables of the considered connection are dropped.
     *
     * @return $this
     */
    protected function dropTablesForMissingMigrations(): self
    {
        $schemaManager = new TestSchemaCleaner();
        foreach ($this->getConfigs() as $config) {
            $config['connection'] = $config['connection'] ?? 'test';
            $migrations = new Migrations($config);
            if ($this->isMigrationMissing($migrations)) {
                $schemaManager->dropSchema($config['connection'], $this->io);
            }
        }

        return $this;
    }

    /**
     * Checks if any migrations are up but missing.
     *
     * @param  Migrations $migrations
     * @return bool
     */
    protected function isMigrationMissing(Migrations $migrations): bool
    {
        foreach ($migrations->status() as $migration) {
            if ($migration['status'] === 'up' && ($migration['missing'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->getConfigReader()->getConfig();
    }

    /**
     * @return ConfigReader
     */
    protected function getConfigReader(): ConfigReader
    {
        return $this->configReader;
    }
}
