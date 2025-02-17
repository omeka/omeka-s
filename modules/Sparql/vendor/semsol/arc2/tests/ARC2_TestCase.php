<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ARC2_TestCase extends TestCase
{
    /**
     * Store configuration to connect with the database.
     *
     * @var array
     */
    protected $dbConfig;

    /**
     * Subject under test.
     */
    protected $fixture;

    protected function setUp(): void
    {
        parent::setUp();

        global $dbConfig;

        $this->dbConfig = $dbConfig;
    }

    /**
     * Depending on the DB config returns current table prefix. It consists of table prefix and store name, if available.
     *
     * @return string
     */
    protected function getSqlTablePrefix()
    {
        $prefix = isset($this->dbConfig['db_table_prefix']) ? $this->dbConfig['db_table_prefix'].'_' : '';
        $prefix .= isset($this->dbConfig['store_name']) ? $this->dbConfig['store_name'].'_' : '';

        return $prefix;
    }
}
