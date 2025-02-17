<?php

/**
 * @author Benjamin Nowack <bnowack@semsol.com>
 * @author Konrad Abicht <konrad.abicht@pier-and-peer.com>
 * @license W3C Software License and GPL
 *
 * @homepage <https://github.com/semsol/arc2>
 */

namespace ARC2\Store\Adapter;

abstract class AbstractAdapter
{
    protected $configuration;
    protected $db;

    /**
     * @var int
     */
    protected $lastRowCount;

    /**
     * Sent queries.
     *
     * @var array
     */
    protected $queries = [];

    /**
     * @param array $configuration default is array()
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
        $this->lastRowCount = 0;

        $this->checkRequirements();
    }

    public function deleteAllTables(): void
    {
        // remove all tables
        $tables = $this->fetchList('SHOW TABLES');
        foreach ($tables as $table) {
            $this->exec('DROP TABLE '.$table['Tables_in_'.$this->configuration['db_name']]);
        }
    }

    public function getAllTables(): array
    {
        $tables = $this->fetchList('SHOW TABLES');
        $result = [];
        foreach ($tables as $table) {
            $result[] = $table['Tables_in_'.$this->configuration['db_name']];
        }

        return $result;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getQueries(): array
    {
        return $this->queries;
    }

    abstract public function checkRequirements();

    /**
     * Connect to server.
     *
     * It returns current object for the connection, such as an instance of \PDO.
     */
    abstract public function connect($existingConnection = null);

    abstract public function disconnect();

    abstract public function escape($value);

    abstract public function exec($sql);

    abstract public function fetchList($sql);

    abstract public function fetchRow($sql);

    abstract public function getAdapterName();

    abstract public function getCollation();

    abstract public function getDBSName();

    abstract public function getLastInsertId();

    abstract public function getErrorMessage();

    abstract public function getNumberOfRows($sql);

    abstract public function getStoreName();

    abstract public function getTablePrefix();

    abstract public function simpleQuery($sql);
}
