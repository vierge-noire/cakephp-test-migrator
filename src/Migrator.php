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

    /**
     * @var string[]
     */
    protected $connectionsWithModifiedStatus = [];

    /**
     * Migrator constructor.
     * @param bool $verbose
     * @param null $configReader
     */
    final public function __construct(bool $verbose, ?ConfigReader $configReader = null)
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
     * @return Migrator
     */
    public static function migrate(array $config = [], $verbose = false): Migrator
    {
        $migrator = new static($verbose);

        $migrator->configReader->readMigrationsInDatasources();
        $migrator->configReader->readConfig($config);
        $migrator->handleMigrationsStatus();

        return $migrator;
    }

    /**
     * Import the schema from a file, or an array of files.
     *
     * @param string $connectionName Connection
     * @param string|string[] $file File to dump
     * @param bool $verbose Set to true to display messages
     * @return void
     * @throws \Exception if the truncation failed
     * @throws \RuntimeException if the file could not be processed
     */
    public static function dump(string $connectionName, $file, bool $verbose = false)
    {
        $files = (array)$file;

        $migrator = new static($verbose);
        $schemaCleaner = new SchemaCleaner($migrator->io);
        $schemaCleaner->drop($connectionName);

        foreach ($files as $file) {
            if (!file_exists($file)) {
                throw new \RuntimeException('The file ' . $file . ' could not found.');
            }

            $sql = file_get_contents($file);
            if ($sql === false) {
                throw new \RuntimeException('The file ' . $file . ' could not read.');
            }

            ConnectionManager::get($connectionName)->execute($sql);

            $migrator->io->success(
                'Dump of schema in file ' . $file . ' for connection ' . $connectionName . ' successful.'
            );
        }

        $schemaCleaner->truncate($connectionName);
    }

    /**
     * Run migrations for all configured migrations.
     *
     * @param string[] $config Migration configuration.
     * @return void
     */
    protected function runMigrations(array $config): void
    {
        $migrations = new Migrations();
        $result = $migrations->migrate($config);

        $msg = 'Migrations for ' . $this->stringifyConfig($config);


        if ($result === true) {
            $this->io->success($msg . ' successfully run.');
        } else {
            $this->io->error( $msg . ' failed.');
        }
    }

    /**
     * If a migration is missing or down, all tables of the considered connection are dropped.
     *
     * @return $this
     */
    protected function handleMigrationsStatus(): self
    {
        $schemaCleaner = new SchemaCleaner($this->io);
        foreach ($this->getConfigs() as &$config) {
            $connectionName = $config['connection'] = $config['connection'] ?? 'test';
            $this->io->info("Reading migrations status for {$this->stringifyConfig($config)}...");
            $migrations = new Migrations($config);
            if ($this->isStatusChanged($migrations)) {
                if (!in_array($connectionName, $this->connectionsWithModifiedStatus))
                {
                    $this->connectionsWithModifiedStatus[] = $connectionName;
                }
            }
        }

        if (empty($this->connectionsWithModifiedStatus)) {
            $this->io->success("No migration changes detected.");

            return $this;
        }

        foreach ($this->connectionsWithModifiedStatus as $connectionName) {
            $schemaCleaner->drop($connectionName);
        }

        foreach ($this->getConfigs() as $config) {
            $this->runMigrations($config);
        }

        foreach ($this->connectionsWithModifiedStatus as $connectionName) {
            $schemaCleaner->truncate($connectionName);
        }

        return $this;
    }

    /**
     * Checks if any migrations are up but missing.
     *
     * @param  Migrations $migrations
     * @return bool
     */
    protected function isStatusChanged(Migrations $migrations): bool
    {
        foreach ($migrations->status() as $migration) {
            if ($migration['status'] === 'up' && ($migration['missing'] ?? false)) {
                $this->io->info('Missing migration(s) detected.');
                return true;
            }
            if ($migration['status'] === 'down') {
                $this->io->info('New migration(s) found.');
                return true;
            }
        }

        return false;
    }

    /**
     * Stringify the migration parameters.
     *
     * @param string[] $config Config array
     * @return string
     */
    protected function stringifyConfig(array $config): string
    {
        $options = [];
        foreach (['connection', 'plugin', 'source', 'target'] as $option) {
            if (isset($config[$option])) {
                $options[] = $option . ' "'.$config[$option].'"';
            }
        }

        return implode(', ', $options);
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

    /**
     * Returns an array of strings with all the connections
     * which migration status have changed and were migrated.
     *
     * @return string[]
     */
    public function getConnectionsWithModifiedStatus(): array
    {
        return $this->connectionsWithModifiedStatus;
    }
}
