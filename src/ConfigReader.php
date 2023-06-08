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


use Cake\Datasource\ConnectionManager;

class ConfigReader
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * Read in the Datasources the 'migrations' key for each
     * active connection
     *
     * @return $this
     */
    public function readMigrationsInDatasources(): self
    {
        foreach ($this->getActiveConnections() as $connectionName) {
            $connection = ConnectionManager::getConfig($connectionName);
            $config = [];

            if (is_array($connection)) {
              $migrations = $connection['migrations'] ?? false;

              if ($migrations) {
                if ($migrations === true) {
                  $config = ['connection' => $connectionName ];
                  $this->normalizeArray($config);
                } elseif (is_array($migrations)) {
                  $config = $migrations;
                  $this->normalizeArray($config);
                  foreach ($config as $k => $v) {
                    $config[$k]['connection'] = $v['connection'] ?? $connectionName;
                  }

                }
                $this->config = array_merge($this->config, $config);
              }
            }
        }

        $this->processConfig();

        return $this;
    }

    /**
     * @param  string[]|array[] $config An array of migration configs
     * @return $this
     */
    public function readConfig(array $config = []): self
    {
        if (!empty($config)) {
            $this->normalizeArray($config);
            $this->config = $config;
        }

        $this->processConfig();

        return $this;
    }

    public function processConfig(): void
    {
        foreach ($this->config as $k => $config) {
            $this->config[$k]['connection'] = $this->config[$k]['connection'] ?? 'test';
        }
        if (empty($this->config)) {
            $this->config = [['connection' => 'test']];
        }
    }

    /**
     * Initialize all connections used by the manager
     *
     * @return array
     */
    public function getActiveConnections(): array
    {
        $connections = ConnectionManager::configured();
        foreach ($connections as $i => $connectionName) {
            if ($this->skipConnection($connectionName)) {
                unset($connections[$i]);
            }
        }

        return $connections;
    }

    /**
     * @param string $connectionName Connection name
     *
     * @return bool
     */
    public function skipConnection(string $connectionName): bool
    {
        // CakePHP 4 solves a DebugKit issue by creating an Sqlite connection
        // in tests/bootstrap.php. This connection should be ignored.
        if ($connectionName === 'test_debug_kit') {
            return true;
        }

        if ($connectionName === 'test' || strpos($connectionName, 'test_') === 0) {
            return false;
        }

        return true;
    }

    /**
     * Make array an array of arrays
     *
     * @param  array $array
     * @return void
     */
    public function normalizeArray(array &$array): void
    {
        if (!empty($array) && !isset($array[0])) {
            $array = [$array];
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
