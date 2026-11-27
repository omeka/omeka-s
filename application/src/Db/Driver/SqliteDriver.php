<?php
namespace Omeka\Db\Driver;

use Doctrine\DBAL\Driver\PDOSqlite\Driver as PdoSqliteDriver;
use Doctrine\DBAL\Exception;
use PDOException;

/**
 * PDO SQLite driver that registers user defined functions without
 * PDO::sqliteCreateFunction(), deprecated in PHP 8.5 and expected to be
 * removed in PHP 9.
 *
 * On PHP 8.4+ the connection is a Pdo\Sqlite subclass (SqlitePdoConnection)
 * and DBAL's default functions (sqrt, mod, locate) plus any
 * driverOptions['userDefinedFunctions'] are registered with
 * Pdo\Sqlite::createFunction(), the approach doctrine/dbal 3.10 takes.
 * SqliteCompatConnection registers the MySQL emulation functions on top of
 * this connection through the same method. Older PHP versions keep the stock
 * DBAL 2 behaviour unchanged.
 */
class SqliteDriver extends PdoSqliteDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        if (PHP_VERSION_ID < 80400) {
            return parent::connect($params, $username, $password, $driverOptions);
        }

        if (isset($driverOptions['userDefinedFunctions'])) {
            $this->_userDefinedFunctions = array_merge(
                $this->_userDefinedFunctions,
                $driverOptions['userDefinedFunctions']
            );
            unset($driverOptions['userDefinedFunctions']);
        }

        try {
            $pdo = new SqlitePdoConnection(
                $this->_constructPdoDsn($params),
                $username,
                $password,
                $driverOptions
            );
        } catch (PDOException $ex) {
            throw Exception::driverException($this, $ex);
        }

        foreach ($this->_userDefinedFunctions as $fn => $data) {
            $pdo->createFunction($fn, $data['callback'], $data['numArgs']);
        }

        return $pdo;
    }
}
