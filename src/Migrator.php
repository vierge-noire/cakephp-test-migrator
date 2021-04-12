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
     * Migrator constructor.
     * @param bool $verbose
     * @param null $configReader
     */
    final public function __construct(bool $verbose, $configReader = null)
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
     * Run migrations for all configured migrations.
     *
     * @param string[] $config Migration configuration.
     * @return void
     */
    protected function runMigrations(array $config)
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
        $schemaManager = new TestSchemaCleaner();
        $connectionsToDrop = [];
        foreach ($this->getConfigs() as &$config) {
            $connectionName = $config['connection'] = $config['connection'] ?? 'test';
            $this->io->info("Migrations check for {$this->stringifyConfig($config)} ...");
            $migrations = new Migrations($config);
            if ($this->isStatusChanged($migrations)) {
                if (!in_array($connectionName, $connectionsToDrop))
                {
                    $connectionsToDrop[] = $connectionName;
                }
            }
        }

        if (empty($connectionsToDrop)) {
            $this->io->success("No migrations changes found.");

            return $this;
        }

        foreach ($connectionsToDrop as $connectionName) {
            $schemaManager->dropSchema($connectionName, $this->io);
        }

        foreach ($this->getConfigs() as $config) {
            $this->runMigrations($config);
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
                $this->io->info('Missing migrations detected.');
                return true;
            }
            if ($migration['status'] === 'down') {
                $this->io->info('New migrations status detected.');
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
}
