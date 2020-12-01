<?php
declare(strict_types=1);

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2020 Juan Pablo Ramirez and Nicolas Masson
 * @link          https://webrider.de/
 * @since         1.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace CakephpTestMigrator;


use CakephpTestSuiteLight\FixtureManager;
use Migrations\Migrations;

class Migrator
{
    /**
     * @var FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var ConfigReader
     */
    protected $configReader;

    final public function __construct()
    {
        $this->fixtureManager = new FixtureManager();
        $this->configReader = new ConfigReader();
        $this->configReader->readMigrationsInDatasources($this->fixtureManager);
    }

    /**
     * General command to run before your tests run
     * E.g. in tests/bootstrap.php
     * @param array $config
     * @return void
     */
    public static function migrate(array $config = [])
    {
        $migrator = new static();

        $migrator
            ->prepareConfig($config)
            ->dropTablesForMissingMigrations()
            ->runAllMigrations();
    }

    /**
     * Run migrations for all configured migrations
     * @return void
     */
    protected function runAllMigrations()
    {
        foreach ($this->getConfig() as $config) {
            $migrations = new Migrations($config);
            $migrations->migrate($config);
        }
    }

    /**
     * If a migration is missing, all tables of the considered connection are dropped
     * @return $this
     */
    protected function dropTablesForMissingMigrations()
    {
        foreach ($this->getConfig() as $config) {
            $config['connection'] = $config['connection'] ?? 'test';
            $migrations = new Migrations($config);
            if ($this->isMigrationMissing($migrations)) {
                $this->getFixtureManager()->dropTables($config['connection']);
            }
        }
        return $this;
    }

    /**
     * Checks if any migrations are up but missing
     * @param Migrations $migrations
     * @return bool
     */
    protected function isMigrationMissing(Migrations $migrations): bool
    {
        $status = $migrations->status();
        foreach ($status as $migration) {
            if ($migration['status'] === 'up' && ($migration['missing'] ?? false)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $config
     * @return $this
     */
    protected function prepareConfig(array $config)
    {
        $this->getConfigReader()->prepareConfig($config);
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig(): array
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
     * @return FixtureManager
     */
    protected function getFixtureManager(): FixtureManager
    {
        return $this->fixtureManager;
    }
}