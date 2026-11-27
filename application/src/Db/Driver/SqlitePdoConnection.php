<?php
namespace Omeka\Db\Driver;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\PDO\Exception;
use Doctrine\DBAL\Driver\PDO\Statement;
use Doctrine\DBAL\Driver\PDOQueryImplementation;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\ParameterType;
use PDO;
use PDOException;
use PDOStatement;
use ReturnTypeWillChange;

/**
 * Doctrine DBAL 2 driver connection built on the Pdo\Sqlite subclass (PHP 8.4+).
 *
 * DBAL 2 requires the driver connection to be the PDO object itself
 * (Doctrine\DBAL\Driver\PDO\Connection extends PDO), so it can never be a
 * Pdo\Sqlite instance and its user defined functions always go through
 * PDO::sqliteCreateFunction(), deprecated in PHP 8.5. This class mirrors
 * Doctrine\DBAL\Driver\PDOConnection (doctrine/dbal 2.13, MIT licence) on top
 * of Pdo\Sqlite so SqliteDriver can register functions with
 * Pdo\Sqlite::createFunction() instead.
 *
 * Pdo\Sqlite only exists on PHP 8.4+; SqliteDriver references this class
 * exclusively on that path, so older PHP versions never autoload it.
 */
class SqlitePdoConnection extends \Pdo\Sqlite implements ConnectionInterface, ServerInfoAwareConnection
{
    use PDOQueryImplementation;

    /**
     * @param string $dsn
     * @param string|null $user
     * @param string|null $password
     * @param mixed[]|null $options
     */
    public function __construct($dsn, $user = null, $password = null, ?array $options = null)
    {
        try {
            parent::__construct($dsn, (string) $user, (string) $password, (array) $options);
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [Statement::class, []]);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    #[ReturnTypeWillChange]
    public function exec($sql)
    {
        try {
            $result = parent::exec($sql);
            assert($result !== false);
            return $result;
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function getServerVersion()
    {
        return PDO::getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * @param string $sql
     * @param array<int, int> $driverOptions
     * @return PDOStatement
     */
    #[ReturnTypeWillChange]
    public function prepare($sql, $driverOptions = [])
    {
        try {
            $statement = parent::prepare($sql, $driverOptions);
            assert($statement instanceof PDOStatement);
            return $statement;
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    #[ReturnTypeWillChange]
    public function quote($value, $type = ParameterType::STRING)
    {
        return parent::quote($value, $type);
    }

    /**
     * @param string|null $name
     * @return string|int|false
     */
    #[ReturnTypeWillChange]
    public function lastInsertId($name = null)
    {
        try {
            if ($name === null) {
                return parent::lastInsertId();
            }
            return parent::lastInsertId($name);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
    }

    public function requiresQueryForServerVersion()
    {
        return false;
    }

    /**
     * @param mixed ...$args
     */
    private function doQuery(...$args): PDOStatement
    {
        try {
            $stmt = parent::query(...$args);
        } catch (PDOException $exception) {
            throw Exception::new($exception);
        }
        assert($stmt instanceof PDOStatement);
        return $stmt;
    }
}
