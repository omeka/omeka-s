<?php

namespace Tests\db_adapter_depended\store\query;

use Tests\ARC2_TestCase;

/**
 * Tests for query method - focus on LOAD queries.
 */
class LoadQueryTest extends ARC2_TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = \ARC2::getStore($this->dbConfig);
        $this->fixture->drop();
        $this->fixture->setup();
    }

    protected function tearDown(): void
    {
        $this->fixture->closeDBCon();
    }

    public function testLoad()
    {
        // check that store is empty
        $res = $this->fixture->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertEquals(0, \count($res['result']['rows']));

        $filepath = 'https://raw.githubusercontent.com/semsol/arc2/'
            .'master/tests/data/turtle/manifest.ttl';
        $this->fixture->query('LOAD <'.$filepath.'>');

        // check that triples were inserted
        $res = $this->fixture->query('
            SELECT *
            FROM <https://raw.githubusercontent.com/semsol/arc2/'
                    .'master/tests/data/turtle/manifest.ttl>
            WHERE {?s ?p ?o.}
        ');
        $this->assertEquals(1860, \count($res['result']['rows']));
    }

    public function testLoadInto()
    {
        // check that store is empty
        $res = $this->fixture->query('SELECT * FROM <http://load-example> WHERE {?s ?p ?o.}');
        $this->assertEquals(0, \count($res['result']['rows']));

        $filepath = 'https://raw.githubusercontent.com/semsol/arc2/'
            .'master/tests/data/turtle/manifest.ttl';
        $this->fixture->query('LOAD <'.$filepath.'> INTO <http://load-example>');

        // check that triples were inserted
        $res = $this->fixture->query('SELECT * FROM <http://load-example> WHERE {?s ?p ?o.}');
        $this->assertEquals(1860, \count($res['result']['rows']));
    }
}
