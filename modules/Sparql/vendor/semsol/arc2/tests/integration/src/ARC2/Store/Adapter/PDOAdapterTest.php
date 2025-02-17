<?php

namespace Tests\integration\src\ARC2\Store\Adapter;

use ARC2\Store\Adapter\PDOAdapter;

class PDOAdapterTest extends AbstractAdapterTest
{
    protected function checkAdapterRequirements()
    {
        // stop, if pdo_mysql is not available
        if (false == \extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('Test skipped, because extension pdo_mysql is not installed.');
        }

        // stop, if pdo_db_protocol is not set in dbConfig
        if (false == isset($this->dbConfig['db_pdo_protocol'])) {
            $this->markTestSkipped(
                'Test skipped, because db_pdo_protocol is not set. Its ok, if this happens in unit test environment.'
            );
        }

        if ('mysql' !== $this->dbConfig['db_pdo_protocol']) {
            $this->markTestSkipped('Skipped, because PDO protocol is not "mysql".');
        }
    }

    protected function getAdapterInstance($configuration)
    {
        return new PDOAdapter($configuration);
    }

    public function testConnectUseGivenConnection()
    {
        $this->fixture->disconnect();

        // create connection outside of the instance
        $dsn = $this->dbConfig['db_pdo_protocol'].':host='.$this->dbConfig['db_host'];
        $dsn .= ';dbname='.$this->dbConfig['db_name'];

        // port
        $dsn .= ';port='.$this->dbConfig['db_port'];

        $dsn .= ';charset=utf8mb4';

        $connection = new \PDO(
            $dsn,
            $this->dbConfig['db_user'],
            $this->dbConfig['db_pwd']
        );

        $connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $connection->setAttribute(\PDO::ERRMODE_EXCEPTION, true);
        $connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_BOTH);
        $connection->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $this->fixture = $this->getAdapterInstance($this->dbConfig);

        // use existing connection
        $this->fixture->connect($connection);

        // if not the same origin, the connection ID differs
        $connectionId = $connection->query('SELECT CONNECTION_ID()')->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($this->fixture->getConnectionId(), $connectionId);

        /*
         * simple test query to check that its working
         */
        $sql = 'CREATE TABLE MyGuests (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY)';
        $this->fixture->simpleQuery($sql);

        $tables = $this->fixture->fetchList('SHOW TABLES');
        $this->assertTrue(\is_array($tables) && 0 < \count($tables));
    }

    public function testEscape()
    {
        $this->assertEquals('\"hallo\"', $this->fixture->escape('"hallo"'));
    }

    public function testGetAdapterName()
    {
        $this->assertEquals('pdo', $this->fixture->getAdapterName());
    }

    public function testGetConnection()
    {
        $this->assertTrue($this->fixture->getConnection() instanceof \PDO);
    }

    public function testGetNumberOfRowsInvalidQuery()
    {
        $this->expectException('Exception');

        $dbs = 'mysql' == $this->fixture->getDBSName() ? 'MySQL' : 'MariaDB';

        $this->expectExceptionMessage(
            "SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your $dbs server version for the right syntax to use near 'of x' at line 1"
        );

        $this->fixture->getNumberOfRows('SHOW TABLES of x');
    }

    public function testQueryInvalid()
    {
        $this->expectException('Exception');

        $dbs = 'mysql' == $this->fixture->getDBSName() ? 'MySQL' : 'MariaDB';

        $this->expectExceptionMessage(
            "SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your $dbs server version for the right syntax to use near 'invalid query' at line 1"
        );

        // invalid query
        $this->assertFalse($this->fixture->simpleQuery('invalid query'));
    }
}
