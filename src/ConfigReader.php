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


use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use CakephpTestSuiteLight\FixtureManager;

class ConfigReader
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * Read in the Datasources the 'migrations' key for each
     * active connection
     * @param FixtureManager $fixtureManager
     * @return $this
     */
    public function readMigrationsInDatasources(FixtureManager $fixtureManager)
    {
        foreach ($fixtureManager->getActiveConnections() as $connectionName) {
            $connection = ConnectionManager::getConfig($connectionName);
            $config = [];

            if (isset($connection['migrations'])) {
                if ($connection['migrations'] === true) {
                    $config = ['connection' => $connectionName ];
                    $this->normalizeArray($config);
                } elseif (is_array($connection['migrations'])) {
                    $config = $connection['migrations'];
                    $this->normalizeArray($config);
                    foreach ($config as $k => $v) {
                        $config[$k]['connection'] = $config[$k]['connection'] ?? $connectionName;
                    }

                }
                $this->config = array_merge($this->config, $config);
            }
        }
        if (empty($this->config)) {
            $this->config = [['connection' => 'test']];
        }
        return $this;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function loadConfig(array $config = [])
    {
        $config = array_merge(Configure::read('TestFixtureMigrations', []), $config);
        if (!empty($config)) {
            $this->normalizeArray($config);
            $this->config = array_merge($this->config, $config);
        }
        return $this;
    }

    /**
     * Make array an array of arrays
     * @param array $array
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