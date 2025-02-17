<?php

/**
 * Adapter to enable usage of PDO functions.
 *
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @author Konrad Abicht <konrad.abicht@pier-and-peer.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 */

namespace ARC2\Store\Adapter;

/**
 * PDO Adapter - Handles database operations using PDO.
 */
class PDOAdapter extends AbstractAdapter
{
    public function checkRequirements()
    {
        if (false == \extension_loaded('pdo_mysql')) {
            throw new \Exception('Extension pdo_mysql is not loaded.');
        }

        if ('mysql' != $this->configuration['db_pdo_protocol']) {
            throw new \Exception('Only "mysql" protocol is supported at the moment.');
        }
    }

    public function getAdapterName()
    {
        return 'pdo';
    }

    public function getAffectedRows(): int
    {
        return $this->lastRowCount;
    }

    /**
     * Connect to server.
     *
     * @return \PDO
     */
    public function connect($existingConnection = null)
    {
        // reuse a given existing connection.
        // it assumes that $existingConnection is a PDO connection object
        if (null !== $existingConnection) {
            $this->db = $existingConnection;

            // create your own connection
        } elseif (false === $this->db instanceof \PDO) {
            /*
             * build connection string
             *
             * - db_pdo_protocol: Protocol to determine server, e.g. mysql
             */
            if (false == isset($this->configuration['db_pdo_protocol'])) {
                throw new \Exception('When using PDO the protocol has to be given (e.g. mysql). Please set db_pdo_protocol in database configuration.');
            }
            $dsn = $this->configuration['db_pdo_protocol'].':host='.$this->configuration['db_host'];
            if (isset($this->configuration['db_name'])) {
                $dsn .= ';dbname='.$this->configuration['db_name'];
            }

            // port
            $dsn .= ';port=';
            $dsn .= isset($this->configuration['db_port']) ? $this->configuration['db_port'] : 3306;

            // set charset
            $dsn .= ';charset=utf8mb4';

            $this->db = new \PDO(
                $dsn,
                $this->configuration['db_user'],
                $this->configuration['db_pwd']
            );

            $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            // errors DONT lead to exceptions
            // ARC2 does not throw any exceptions, instead collects errors in a hidden array.
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // default fetch mode is associative
            $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // from source: http://php.net/manual/de/ref.pdo-mysql.php
            // If this attribute is set to TRUE on a PDOStatement, the MySQL driver will use
            // the buffered versions of the MySQL API. But we wont rely on that, setting it false.
            $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

            // in MySQL, this setting allows bigger JOINs
            $stmt = $this->db->prepare('SET SESSION SQL_BIG_SELECTS=1');
            $stmt->execute();
            $stmt->closeCursor();

            /*
             * with MySQL 5.6 we ran into exceptions like:
             *      PDOException: SQLSTATE[42000]: Syntax error or access violation:
             *      1140 In aggregated query without GROUP BY, expression #1 of SELECT list contains
             *      nonaggregated column 'testdb.T_0_0_0.p'; this is incompatible with
             *      sql_mode=only_full_group_by
             *
             * the following query makes this right.
             * FYI: https://stackoverflow.com/questions/23921117/disable-only-full-group-by
             */
            $stmt = $this->db->prepare("SET sql_mode = ''");
            $stmt->execute();
            $stmt->closeCursor();
        }

        return $this->db;
    }

    /**
     * @return void
     */
    public function disconnect()
    {
        // FYI: https://stackoverflow.com/questions/18277233/pdo-closing-connection
        $this->db = null;
    }

    public function escape($value)
    {
        $quoted = $this->db->quote($value);

        /*
         * fixes the case, that we have double quoted strings like:
         *      ''x1''
         *
         * remember, this value will be surrounded by quotes later on!
         * so we don't send it back with quotes around.
         */
        if ("'" == substr($quoted, 0, 1)) {
            $quoted = substr($quoted, 1, \strlen($quoted) - 2);
        }

        return $quoted;
    }

    /**
     * @param string $sql
     *
     * @return array
     */
    public function fetchList($sql)
    {
        // save query
        $this->queries[] = [
            'query' => $sql,
            'by_function' => 'fetchList',
        ];

        if (null == $this->db) {
            $this->connect();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();

        return $rows;
    }

    public function fetchRow($sql)
    {
        // save query
        $this->queries[] = [
            'query' => $sql,
            'by_function' => 'fetchRow',
        ];

        if (null == $this->db) {
            $this->connect();
        }

        $row = false;
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (0 < \count($rows)) {
            $row = array_values($rows)[0];
        }
        $stmt->closeCursor();

        return $row;
    }

    public function getCollation()
    {
        $row = $this->fetchRow('SHOW TABLE STATUS LIKE "'.$this->getTablePrefix().'setting"');

        if (isset($row['Collation'])) {
            return $row['Collation'];
        } else {
            return '';
        }
    }

    public function getConnection()
    {
        return $this->db;
    }

    public function getConnectionId()
    {
        return $this->db->query('SELECT CONNECTION_ID()')->fetch(\PDO::FETCH_ASSOC);
    }

    public function getDBSName()
    {
        if (null == $this->db) {
            return;
        }

        $clientVersion = strtolower($this->db->getAttribute(\PDO::ATTR_CLIENT_VERSION));
        $serverVersion = strtolower($this->db->getAttribute(\PDO::ATTR_SERVER_VERSION));
        if (str_contains($clientVersion, 'mariadb') || str_contains($serverVersion, 'mariadb')) {
            $return = 'mariadb';
        } elseif (str_contains($clientVersion, 'mysql') || str_contains($serverVersion, 'mysql')) {
            $return = 'mysql';
        } else {
            $return = null;
        }

        return $return;
    }

    /**
     * Returns the version of the database server like 05.00.12
     */
    public function getServerVersion(): string
    {
        if ($this->db instanceof \PDO) {
            return $this->db->query('select version()')->fetchColumn();
        }

        throw new \Exception('You need to connect to DB server first. Use connect() before this function.');
    }

    public function getErrorCode()
    {
        return $this->db->errorCode();
    }

    public function getErrorMessage()
    {
        return $this->db->errorInfo()[2];
    }

    public function getLastInsertId()
    {
        return $this->db->lastInsertId();
    }

    public function getNumberOfRows($sql)
    {
        // save query
        $this->queries[] = [
            'query' => $sql,
            'by_function' => 'getNumberOfRows',
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rowCount = \count($stmt->fetchAll());
        $stmt->closeCursor();

        return $rowCount;
    }

    public function getStoreName()
    {
        if (isset($this->configuration['store_name'])) {
            return $this->configuration['store_name'];
        }

        return 'arc';
    }

    public function getTablePrefix()
    {
        $prefix = '';
        if (isset($this->configuration['db_table_prefix'])) {
            $prefix = $this->configuration['db_table_prefix'].'_';
        }

        $prefix .= $this->getStoreName().'_';

        return $prefix;
    }

    /**
     * @param string $sql Query
     *
     * @return bool true if query ran fine, false otherwise
     */
    public function simpleQuery($sql)
    {
        // save query
        $this->queries[] = [
            'query' => $sql,
            'by_function' => 'simpleQuery',
        ];

        if (false === $this->db instanceof \PDO) {
            $this->connect();
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $this->lastRowCount = $stmt->rowCount();
        $stmt->closeCursor();

        return true;
    }

    /**
     * Encapsulates internal PDO::exec call. This allows us to extend it, e.g. with caching functionality.
     *
     * @param string $sql
     *
     * @return int number of affected rows
     */
    public function exec($sql)
    {
        // save query
        $this->queries[] = [
            'query' => $sql,
            'by_function' => 'exec',
        ];

        if (null == $this->db) {
            $this->connect();
        }

        return $this->db->exec($sql);
    }
}
