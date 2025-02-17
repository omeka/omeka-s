<?php

require_once __DIR__.'/../vendor/autoload.php';

error_reporting(\E_ALL);

require 'ARC2_TestHandler.php';

global $dbConfig;

$dbConfig = null;

/*
 * For local development only.
 *
 * Copy config.php.dist to config.php, adapt your values and run PHPUnit.
 */

if (file_exists(__DIR__.'/config.php')) {
    $dbConfig = require 'config.php';
} elseif (isset($_SERVER['GITHUB_PATH'])) {
    /*
     * For CI Github workflow only.
     *
     * Parameter are set from outside using environment variables.
     * Please check YAML files in .github/workflows for details.
     */

    $dbConfig = [
        'db_name' => 'arc2_test',
        'db_user' => 'root',
        'db_pwd' => 'Pass123',
        'db_host' => '127.0.0.1',
        'db_port' => $_SERVER['DB_PORT'] ?? 3306,
    ];

    /*
     * DB Adapter
     */
    $dbConfig['db_adapter'] = getenv('DB_ADAPTER') ?? $_SERVER['DB_ADAPTER'];
    if (false === $dbConfig['db_adapter']) {
        $dbConfig['db_adapter'] = 'pdo';
    }

    // in pre 3.x ARC2 supported mysqli too. because of that the switch is still there just in case
    // another adapter will be added in the future

    if ('pdo' == $dbConfig['db_adapter']) {
        $dbConfig['db_pdo_protocol'] = getenv('DB_PDO_PROTOCOL') ?? $_SERVER['DB_PDO_PROTOCOL'];
        if (false === $dbConfig['db_pdo_protocol']) {
            $dbConfig['db_pdo_protocol'] = 'mysql';
        }

        if (is_string($dbConfig['db_pdo_protocol']) && '' !== $dbConfig['db_pdo_protocol']) {
            // OK
        } else {
            $msg = 'Neither environment variable DB_PDO_PROTOCOL nor $_SERVER["DB_PDO_PROTOCOL"] are set.'
                .' Possible values are: mysql';
            throw new \Exception($msg);
        }
    } else {
        throw new Exception('Neither environment variable DB_ADAPTER nor $_SERVER["DB_ADAPTER"] are set.');
    }

    // set defaults for dbConfig entries
    if (false == isset($dbConfig['store_name'])) {
        $dbConfig['store_name'] = 'arc';
    }

    $dbConfig['db_table_prefix'] = $dbConfig['db_table_prefix'] ?? null;
} else {
    $dbConfig = require 'config.php.dist';
}
