<?php

namespace Tests\integration\src\ARC2\Store\Adapter;

use Tests\ARC2_TestCase;

abstract class AbstractAdapterTest extends ARC2_TestCase
{
    abstract protected function checkAdapterRequirements();

    abstract protected function getAdapterInstance($config);

    abstract public function testConnectUseGivenConnection();

    abstract public function testEscape();

    abstract public function testGetAdapterName();

    abstract public function testGetConnection();

    abstract public function testGetNumberOfRowsInvalidQuery();

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkAdapterRequirements();

        $this->fixture = $this->getAdapterInstance($this->dbConfig);
        $this->fixture->connect();

        // remove all tables
        $this->fixture->deleteAllTables();
    }

    protected function tearDown(): void
    {
        if (null !== $this->fixture) {
            $this->fixture->disconnect();
        }
    }

    protected function dropAllTables()
    {
        // remove all tables
        $tables = $this->fixture->fetchList('SHOW TABLES');
        foreach ($tables as $table) {
            $this->fixture->exec('DROP TABLE '.$table['Tables_in_'.$this->dbConfig['db_name']]);
        }
    }

    /*
     * Tests for connect
     */

    public function testConnectCreateNewConnection()
    {
        $this->fixture->disconnect();

        // do explicit reconnect
        $this->fixture = $this->getAdapterInstance($this->dbConfig);
        $this->fixture->connect();

        $tables = $this->fixture->fetchList('SHOW TABLES');
        $this->assertTrue(\is_array($tables));
    }

    /*
     * Tests for exec
     */

    public function testExec()
    {
        $this->fixture->exec('CREATE TABLE users (id INT(6), name VARCHAR(30) NOT NULL)');
        $this->fixture->exec('INSERT INTO users (id, name) VALUE (1, "foobar");');
        $this->fixture->exec('INSERT INTO users (id, name) VALUE (2, "foobar2");');

        $this->assertEquals(2, $this->fixture->exec('DELETE FROM users;'));
    }

    /*
     * Tests for fetchRow
     */

    public function testFetchRow()
    {
        // valid query
        $sql = 'CREATE TABLE users (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(30) NOT NULL
        )';
        $this->fixture->exec($sql);
        $this->assertFalse($this->fixture->fetchRow('SELECT * FROM users'));

        // add data
        $this->fixture->exec('INSERT INTO users (id, name) VALUE (1, "foobar");');
        $this->assertEquals(
            [
                'id' => 1,
                'name' => 'foobar',
            ],
            $this->fixture->fetchRow('SELECT * FROM users WHERE id = 1;')
        );
    }

    /*
     * Tests for fetchList
     */

    public function testFetchList()
    {
        // valid query
        $sql = 'CREATE TABLE users (
            id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(30) NOT NULL
        )';
        $this->fixture->exec($sql);
        $this->assertEquals([], $this->fixture->fetchList('SELECT * FROM users'));

        // add data
        $this->fixture->exec('INSERT INTO users (id, name) VALUE (1, "foobar");');
        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'name' => 'foobar',
                ],
            ],
            $this->fixture->fetchList('SELECT * FROM users')
        );
    }

    /*
     * Tests for getCollation
     */

    public function testGetCollation()
    {
        // g2t table
        if (isset($this->dbConfig['db_table_prefix'])) {
            $table = $this->dbConfig['db_table_prefix'].'_';
        } else {
            $table = '';
        }
        if (isset($this->dbConfig['store_name'])) {
            $table .= $this->dbConfig['store_name'].'_';
        }
        $table .= 'setting';

        // create setting table which is used to determine collation
        $sql = 'CREATE TABLE '.$table.' (
          k char(32) NOT NULL,
          val text NOT NULL,
          UNIQUE KEY (k)
        ) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci DELAY_KEY_WRITE = 1';
        $this->fixture->exec($sql);

        $this->assertStringContainsString('_unicode_ci', $this->fixture->getCollation());
    }

    // setting table not there
    public function testGetCollationNoReferenceTable()
    {
        $this->assertEquals('', $this->fixture->getCollation());
    }

    /*
     * Tests for getDBSName
     */

    public function testGetDBSName()
    {
        // connect and check
        $this->fixture->connect();
        $this->assertTrue(
            \in_array($this->fixture->getDBSName(), ['sqlite', 'mariadb', 'mysql']),
            'Found: '.$this->fixture->getDBSName()
        );
    }

    public function testGetDBSNameNoConnection()
    {
        // disconnect current connection
        $this->fixture->disconnect();

        // create new instance, but dont connect
        $db = $this->getAdapterInstance($this->dbConfig);

        $this->assertNull($db->getDBSName());
    }

    /*
     * Tests for getNumberOfRows
     */

    public function testGetNumberOfRows()
    {
        // create test table
        $this->fixture->exec('CREATE TABLE pet (name VARCHAR(20));');

        $this->assertEquals(1, $this->fixture->getNumberOfRows('SHOW TABLES'));
    }

    /*
     * Tests for query
     */

    public function testQuery()
    {
        // valid query
        $sql = 'CREATE TABLE MyGuests (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY)';
        $this->fixture->exec($sql);

        $foundTable = false;
        foreach ($this->fixture->getAllTables() as $table) {
            if ('MyGuests' == $table) {
                $foundTable = true;
                break;
            }
        }
        $this->assertTrue($foundTable, 'Expected table not found.');
    }

    /*
     * Tests for getServerVersion
     */

    public function testGetServerVersion()
    {
        $this->assertEquals(
            $this->fixture->getConnection()->query('select version()')->fetchColumn(),
            $this->fixture->getServerVersion()
        );
    }

    /*
     * Tests for getStoreName
     */

    public function testGetStoreName()
    {
        $this->assertEquals('arc', $this->fixture->getStoreName());
    }

    public function testGetStoreNameNotDefined()
    {
        $this->fixture->disconnect();

        $copyOfDbConfig = $this->dbConfig;
        unset($copyOfDbConfig['store_name']);

        $db = $this->getAdapterInstance($copyOfDbConfig);

        $this->assertEquals('arc', $db->getStoreName());
    }

    /*
     * Tests for exec
     */

    public function testSimpleQueryNoConnection()
    {
        // test, that it creates a connection itself, when calling exec
        $this->fixture->disconnect();

        $db = $this->getAdapterInstance($this->dbConfig);
        $sql = 'CREATE TABLE MyGuests (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY)';
        $this->assertEquals(0, $db->exec($sql));
    }
}
