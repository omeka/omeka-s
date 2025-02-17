<?php

namespace Tests\db_adapter_depended\store;

use Tests\ARC2_TestCase;

class ARC2_StoreTest extends ARC2_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = \ARC2::getStore($this->dbConfig);
        $this->fixture->createDBCon();

        // remove all tables
        $this->fixture->getDBObject()->deleteAllTables();

        // fresh setup of ARC2
        $this->fixture->setup();
    }

    protected function tearDown(): void
    {
        $this->fixture->closeDBCon();
    }

    /**
     * Returns a list of all available graph URIs of the store. It can also respect access control,
     * to only returned available graphs in the current context. But that depends on the implementation
     * and can differ.
     *
     * @return array simple array of key-value-pairs, which consists of graph URIs as values
     */
    protected function getGraphs()
    {
        $g2t = $this->fixture->getTablePrefix().'g2t';
        $id2val = $this->fixture->getTablePrefix().'id2val';

        // collects all values which have an ID (column g) in the g2t table.
        $query = 'SELECT id2val.val AS graphUri
            FROM '.$g2t.' g2t
            LEFT JOIN '.$id2val.' id2val ON g2t.g = id2val.id
            GROUP BY g';

        // send SQL query
        $list = $this->fixture->getDBObject()->fetchList($query);
        $graphs = [];

        // collect graph URI's
        foreach ($list as $row) {
            $graphs[] = $row['graphUri'];
        }

        return $graphs;
    }

    public function testSetup()
    {
        $this->fixture->reset();

        $this->fixture->setup();

        $this->assertTrue($this->fixture->isSetup());
    }

    /*
     * Tests for caching behavior
     */

    public function testCaching()
    {
        if (false == $this->fixture->cacheEnabled()) {
            $this->markTestSkipped('Skip tests of ARC2_Store caching, because cache is not enabled.');
        }

        // add test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $selectQuery = 'SELECT * FROM <http://example.com/> {?s ?p ?o.}';

        // check that query is not known in cache
        $this->assertFalse($this->dbConfig['cache_instance']->has(hash('sha1', $selectQuery)));

        $result = $this->fixture->query($selectQuery);
        unset($result['query_time']);
        $this->assertEquals(1, \count($result['result']['rows']));

        $this->assertTrue($this->dbConfig['cache_instance']->has(hash('sha1', $selectQuery)));

        // compare cached and raw result
        $cachedResult = $this->fixture->query($selectQuery);
        unset($cachedResult['query_time']);
        $this->assertEquals($result, $cachedResult);
    }

    /*
     * Tests for changeNamespaceURI
     */

    public function testChangeNamespaceURIEmptyStore()
    {
        $res = $this->fixture->changeNamespaceURI(
            'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'urn:rdf'
        );

        $this->assertEquals(
            [
                'id_replacements' => 0,
                'triple_updates' => 0,
            ],
            $res
        );
    }

    public function testChangeNamespaceURIFilledStore()
    {
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://pref/s> <http://pref/p1> "baz" .
        }');

        $res = $this->fixture->changeNamespaceURI(
            'http://pref/',
            'urn:rdf'
        );

        $this->assertEquals(
            [
                'id_replacements' => 2,
                'triple_updates' => 0,
            ],
            $res
        );
    }

    /*
     * Tests for countDBProcesses
     */

    public function testCountDBProcesses()
    {
        $this->assertTrue(0 < $this->fixture->countDBProcesses());
    }

    /*
     * Tests for createBackup
     */

    public function testCreateBackup()
    {
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $this->fixture->createBackup('/tmp/backup.txt');

        $expectedXML = <<<XML
<?xml version="1.0"?>
<sparql xmlns="http://www.w3.org/2005/sparql-results#">
  <head>
    <variable name="s"/>
    <variable name="p"/>
    <variable name="o"/>
    <variable name="g"/>
  </head>
  <results>
    <result>
      <binding name="s">
        <uri>http://s</uri>
      </binding>
      <binding name="p">
        <uri>http://p1</uri>
      </binding>
      <binding name="o">
        <literal>baz</literal>
      </binding>
      <binding name="g">
        <uri>http://example.com/</uri>
      </binding>
    </result>
  </results>
</sparql>

XML;
        $this->assertEquals(file_get_contents('/tmp/backup.txt'), $expectedXML);
    }

    /*
     * Tests for closeDBCon
     */

    public function testCloseDBCon()
    {
        $this->assertTrue(isset($this->fixture->a['db_object']));

        $this->fixture->closeDBCon();

        $this->assertFalse(isset($this->fixture->a['db_object']));
    }

    /*
     * Tests for delete
     */

    public function testDelete()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
            <http://s> <http://xmlns.com/foaf/0.1/name> "label1" .
        }');

        $res = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertEquals(2, \count($res['result']['rows']));

        // remove graph
        $this->fixture->delete(false, 'http://example.com/');

        $res = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertEquals(0, \count($res['result']['rows']));
    }

    /*
     * Tests for drop
     */

    public function testDrop()
    {
        // make sure all tables were created
        $this->fixture->setup();
        $this->assertEquals(6, \count($this->fixture->getDBObject()->getAllTables()));

        // remove all tables
        $this->fixture->drop();

        // check that all tables were removed
        $this->assertEquals(0, \count($this->fixture->getDBObject()->getAllTables()));
    }

    /*
     * Tests for dump
     */

    public function testDump()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        // fixed dump call using error_reporting to avoid
        // Cannot modify header information - headers already sent by (output started at
        // ./vendor/phpunit/phpunit/src/Util/Printer.php:110)
        // thanks to https://github.com/sebastianbergmann/phpunit/issues/720#issuecomment-364024753
        error_reporting(0);
        ob_start();
        $this->fixture->dump();
        $dumpContent = ob_get_clean();
        error_reporting(\E_ALL);

        $expectedXML = <<<XML
<?xml version="1.0"?>
<sparql xmlns="http://www.w3.org/2005/sparql-results#">
  <head>
    <variable name="s"/>
    <variable name="p"/>
    <variable name="o"/>
    <variable name="g"/>
  </head>
  <results>
    <result>
      <binding name="s">
        <uri>http://s</uri>
      </binding>
      <binding name="p">
        <uri>http://p1</uri>
      </binding>
      <binding name="o">
        <literal>baz</literal>
      </binding>
      <binding name="g">
        <uri>http://example.com/</uri>
      </binding>
    </result>
  </results>
</sparql>

XML;
        $this->assertEquals($expectedXML, $dumpContent);
    }

    /*
     * Tests for enableFulltextSearch
     */

    public function testEnableFulltextSearch()
    {
        if (str_starts_with($this->fixture->getDBObject()->getServerVersion(), '5.5.')) {
            $this->markTestSkipped('InnoDB does not support fulltext in MySQL 5.5.x');
        }

        $res1 = $this->fixture->enableFulltextSearch();
        $res2 = $this->fixture->disableFulltextSearch();

        $this->assertNull($res1);

        $this->assertEquals(1, $res2);

        $this->assertEquals(0, $this->fixture->a['db_object']->getErrorCode());
        $this->assertEquals('', $this->fixture->a['db_object']->getErrorMessage());
    }

    /*
     * Tests for getDBVersion
     */

    // just check pattern
    public function testGetDBVersion()
    {
        $this->assertEquals(
            $this->fixture->getDBObject()->getConnection()->query('select version()')->fetchColumn(),
            $this->fixture->getDBVersion()
        );
    }

    /*
     * Tests for getDBCon
     */

    public function testGetDBCon()
    {
        // TODO use a different check, if mariadb or mysql is used
        $this->assertTrue(false !== $this->fixture->getDBCon());
    }

    /*
     * Tests for getSetting and setSetting
     */

    public function testGetAndSetSetting()
    {
        $this->assertEquals(0, $this->fixture->getSetting('foo'));

        $this->fixture->setSetting('foo', 'bar');

        $this->assertEquals('bar', $this->fixture->getSetting('foo'));
    }

    public function testGetAndSetSettingUseDefault()
    {
        $this->assertEquals('no-entry', $this->fixture->getSetting('not-available-'.time(), 'no-entry'));
    }

    public function testGetAndSetSettingExistingSetting()
    {
        $this->assertEquals(0, $this->fixture->getSetting('foo'));

        $this->fixture->setSetting('foo', 'bar');
        $this->fixture->setSetting('foo', 'bar2'); // overrides existing setting

        $this->assertEquals('bar2', $this->fixture->getSetting('foo'));
    }

    /*
     * Tests for getLabelProps
     */

    public function testGetLabelProps()
    {
        $this->assertEquals(
            [
                'http://www.w3.org/2000/01/rdf-schema#label',
                'http://xmlns.com/foaf/0.1/name',
                'http://purl.org/dc/elements/1.1/title',
                'http://purl.org/rss/1.0/title',
                'http://www.w3.org/2004/02/skos/core#prefLabel',
                'http://xmlns.com/foaf/0.1/nick',
            ],
            $this->fixture->getLabelProps()
        );
    }

    /*
     * Tests for getResourceLabel
     */

    public function testGetResourceLabel()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
            <http://s> <http://xmlns.com/foaf/0.1/name> "label1" .
        }');

        $res = $this->fixture->getResourceLabel('http://s');

        $this->assertEquals('label1', $res);
    }

    public function testGetResourceLabelNoData()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $res = $this->fixture->getResourceLabel('http://s');

        $this->assertEquals('s', $res);
    }

    /*
     * Tests for getResourcePredicates
     */

    public function testGetResourcePredicates()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
            <http://s> <http://p2> "bar" .
        }');

        $res = $this->fixture->getResourcePredicates('http://s');

        $this->assertEquals(
            [
                'http://p1' => [],
                'http://p2' => [],
            ],
            $res
        );
    }

    public function testGetResourcePredicatesMultipleGraphs()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
            <http://s> <http://p2> "bar" .
        }');

        $this->fixture->query('INSERT INTO <http://example.com/2> {
            <http://s> <http://p3> "baz" .
            <http://s> <http://p4> "bar" .
        }');

        $res = $this->fixture->getResourcePredicates('http://s');

        $this->assertEquals(
            [
                'http://p1' => [],
                'http://p2' => [],
                'http://p3' => [],
                'http://p4' => [],
            ],
            $res
        );
    }

    /*
     * Tests for getPredicateRange
     */

    public function testGetPredicateRange()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://p1> <http://www.w3.org/2000/01/rdf-schema#range> <http://foobar> .
        }');

        $res = $this->fixture->getPredicateRange('http://p1');

        $this->assertEquals('http://foobar', $res);
    }

    public function testGetPredicateRangeNotFound()
    {
        $res = $this->fixture->getPredicateRange('http://not-available');

        $this->assertEquals('', $res);
    }

    /*
     * Tests for getIDValue
     */

    public function testGetIDValue()
    {
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://p1> <http://www.w3.org/2000/01/rdf-schema#range> <http://foobar> .
        }');

        $res = $this->fixture->getIDValue(1);

        $this->assertEquals('http://example.com/', $res);
    }

    public function testGetIDValueNoData()
    {
        $res = $this->fixture->getIDValue(1);

        $this->assertEquals(0, $res);
    }

    /**
     * Saft frameworks ARC2 addition fails to run with ARC2 2.4.
     *
     * https://github.com/SaftIng/Saft/tree/master/src/Saft/Addition/ARC2
     */
    public function testInsertSaftRegressionTest1()
    {
        $res = $this->fixture->query('SELECT * FROM <http://example.com/> WHERE { ?s ?p ?o. } ');
        $this->assertEquals(0, \count($res['result']['rows']));

        $this->fixture->insert(
            file_get_contents(__DIR__.'/../../data/nt/saft-arc2-addition-regression1.nt'),
            'http://example.com/'
        );

        $res1 = $this->fixture->query('SELECT * FROM <http://example.com/> WHERE { ?s ?p ?o. } ');
        $this->assertEquals(442, \count($res1['result']['rows']));

        $res2 = $this->fixture->query('SELECT * WHERE { ?s ?p ?o. } ');
        $this->assertEquals(442, \count($res2['result']['rows']));
    }

    /**
     * Saft frameworks ARC2 addition fails to run with ARC2 2.4.
     *
     * https://github.com/SaftIng/Saft/tree/master/src/Saft/Addition/ARC2
     *
     * This tests checks gathering of freshly created resources.
     */
    public function testInsertSaftRegressionTest2()
    {
        $res = $this->fixture->query('INSERT INTO <http://localhost/Saft/TestGraph/> {<http://foo/1> <http://foo/2> <http://foo/3> . }');

        $res1 = $this->fixture->query('SELECT * FROM <http://localhost/Saft/TestGraph/> WHERE {?s ?p ?o.}');
        $this->assertEquals(1, \count($res1['result']['rows']));

        $res2 = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertEquals(1, \count($res2['result']['rows']));

        $res2 = $this->fixture->query('SELECT ?s ?p ?o WHERE {?s ?p ?o.}');
        $this->assertEquals(1, \count($res2['result']['rows']));
    }

    /**
     * Saft frameworks ARC2 addition fails to run with ARC2 2.4.
     *
     * This test checks side effects of update operations on different graphs.
     *
     * We add 1 triple to 1 and another to another graph. Afterwards removing the first graph.
     * In the end should the second graph still containg his triple.
     */
    public function testInsertSaftRegressionTest3()
    {
        $this->fixture->query(
            'INSERT INTO <http://localhost/Saft/TestGraph/> {<http://localhost/Saft/TestGraph/> <http://localhost/Saft/TestGraph/> <http://localhost/Saft/TestGraph/> . }'
        );
        $this->fixture->query(
            'INSERT INTO <http://second-graph/> {<http://second-graph/0> <http://second-graph/1> <http://second-graph/2> . }'
        );
        $this->fixture->query(
            'DELETE FROM <http://localhost/Saft/TestGraph/>'
        );

        $res = $this->fixture->query('SELECT * FROM <http://second-graph/> WHERE {?s ?p ?o.}');
        $this->assertEquals(1, \count($res['result']['rows']));
    }

    public function testMultipleInsertQuerysInDifferentGraphs()
    {
        $this->markTestSkipped(
            'Adding the same triple into two graphs does not work.'
            .\PHP_EOL.'Bug report: https://github.com/semsol/arc2/issues/114'
        );

        /*
         * the following checks will not go through because of the bug in #114
         */

        $this->fixture->query('INSERT INTO <http://graph1/> {<http://foo/1> <http://foo/2> <http://foo/3> . }');
        $this->fixture->query('INSERT INTO <http://graph2/> {<http://foo/4> <http://foo/5> <http://foo/6> . }');
        $this->fixture->query('INSERT INTO <http://graph2/> {<http://foo/a> <http://foo/b> <http://foo/c> . }');

        $res = $this->fixture->query('SELECT * FROM <http://graph1/> WHERE {?s ?p ?o.}');
        $this->assertEquals(1, \count($res['result']['rows']));

        $res = $this->fixture->query('SELECT * FROM <http://graph2/> WHERE {?s ?p ?o.}');
        $this->assertEquals(2, \count($res['result']['rows']));

        $res = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertEquals(3, \count($res['result']['rows']));
    }

    /*
     * Tests for logQuery
     */

    public function testLogQuery()
    {
        $logFile = 'arc_query_log.txt';

        $this->assertFalse(file_exists($logFile));

        $this->fixture->logQuery('query1');

        $this->assertTrue(file_exists($logFile));
        unlink($logFile);
    }

    /*
     * Tests for renameTo
     */

    public function testRenameTo()
    {
        /*
         * remove all tables
         */
        $this->fixture->getDBObject()->deleteAllTables();

        /*
         * create fresh store and check tables
         */
        $this->fixture->setup();

        if (isset($this->dbConfig['db_table_prefix'])) {
            foreach ($this->fixture->getDBObject()->getAllTables() as $table) {
                $this->assertTrue(str_contains($table, $this->dbConfig['db_table_prefix'].'_'));
            }
        }

        /*
         * rename store
         */
        $prefix = 'new_store';
        $this->fixture->renameTo($prefix);

        /*
         * check for new prefixes
         */
        foreach ($this->fixture->getDBObject()->getAllTables() as $table) {
            // ignore SQLite tables
            if ('sqlite_sequence' == $table) {
                continue;
            }
            $this->assertTrue(str_contains($table, $prefix), 'Renaming failed for '.$table);
        }
    }

    /*
     * Tests for replace
     */

    public function testReplace()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://original/> {
            <http://s> <http://p1> "baz" .
            <http://s> <http://xmlns.com/foaf/0.1/name> "label1" .
        }');

        $res = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertEquals(2, \count($res['result']['rows']));

        $this->assertEquals(
            [
                'http://original/',
            ],
            $this->getGraphs()
        );

        // replace graph
        $returnVal = $this->fixture->replace(false, 'http://original/', 'http://replacement/');

        // check triples
        $res = $this->fixture->query('SELECT * FROM <http://original/> WHERE {?s ?p ?o.}');
        $this->assertEquals(0, \count($res['result']['rows']));

        // get available graphs
        $this->assertEquals(0, \count($this->getGraphs()));

        $res = $this->fixture->query('SELECT * FROM <http://replacement/> WHERE {?s ?p ?o.}');
        // TODO this does not makes sense, why are there no triples?
        $this->assertEquals(0, \count($res['result']['rows']));

        $res = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        // TODO this does not makes sense, why are there no triples?
        $this->assertEquals(0, \count($res['result']['rows']));

        // check return value
        $this->assertEquals(
            [
                [
                    't_count' => 2,
                    'delete_time' => $returnVal[0]['delete_time'],
                    'index_update_time' => $returnVal[0]['index_update_time'],
                ],
                false,
            ],
            $returnVal
        );
    }

    /*
     * Tests for replicateTo
     */

    public function testReplicateTo()
    {
        if (
            '05-06' == substr($this->fixture->getDBVersion(), 0, 5)
        ) {
            $this->markTestSkipped(
                'With MySQL 5.6 ARC2_Store::replicateTo does not work. Tables keep their names.'
            );
        }

        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "2009-05-28T18:03:38+09:00" .
            <http://s> <http://p1> "2009-05-28T18:03:38+09:00GMT" .
            <http://s> <http://p1> "21 August 2007" .
        }');

        // replicate
        $this->fixture->replicateTo('replicate');

        /*
         * check for new prefixes
         */
        $tables = $this->fixture->getDBObject()->fetchList('SHOW TABLES');
        $foundArcPrefix = $foundReplicatePrefix = false;
        foreach ($tables as $table) {
            // check for original table
            if (str_contains($table['Tables_in_'.$this->dbConfig['db_name']], $this->dbConfig['store_name'].'_')) {
                $foundArcPrefix = true;
                // check for replicated table
            } elseif (str_contains($table['Tables_in_'.$this->dbConfig['db_name']], 'replicate_')) {
                $foundReplicatePrefix = true;
            }
        }

        $this->assertTrue($foundArcPrefix);
        $this->assertTrue($foundReplicatePrefix);
    }

    /*
     * Tests for reset
     */

    public function testResetKeepSettings()
    {
        $this->fixture->setSetting('foo', 'bar');
        $this->assertEquals(1, $this->fixture->hasSetting('foo'));

        $this->fixture->reset(1);

        $this->assertEquals(1, $this->fixture->hasSetting('foo'));
    }

    public function testMultipleInsertsSameStore()
    {
        $this->fixture->query('INSERT INTO <http://ex/> {<http://a> <http://b> <http://c> . }');
        $this->fixture->query('INSERT INTO <http://ex/> {<http://a2> <http://b2> <http://c2> . }');

        $res = $this->fixture->query('SELECT * FROM <http://ex/> WHERE {?s ?p ?o.}');
        $this->assertEquals(2, \count($res['result']['rows']));
    }
}
