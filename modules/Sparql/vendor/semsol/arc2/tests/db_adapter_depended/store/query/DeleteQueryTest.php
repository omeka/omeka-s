<?php

namespace Tests\db_adapter_depended\store\query;

use Tests\ARC2_TestCase;

/**
 * Tests for query method - focus on DELETE queries.
 */
class DeleteQueryTest extends ARC2_TestCase
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

    protected function runSPOQuery($g = null)
    {
        return null == $g
            ? $this->fixture->query('SELECT * WHERE {?s ?p ?o.}')
            : $this->fixture->query('SELECT * FROM <'.$g.'> WHERE {?s ?p ?o.}');
    }

    public function testDelete()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/1> {
            <http://s> <http://p1> "baz" .
        }');
        $this->fixture->query('INSERT INTO <http://example.com/2> {
            <http://s> <http://p1> "bar" .
        }');

        $this->assertEquals(2, \count($this->runSPOQuery()['result']['rows']));

        $this->fixture->query('DELETE {<http://s> ?p ?o .}');

        $this->assertEquals(0, \count($this->runSPOQuery()['result']['rows']));
    }

    public function testDelete2()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/1> {
            <http://s> <http://p1> "baz" .
        }');
        $this->fixture->query('INSERT INTO <http://example.com/2> {
            <http://s> <http://p2> "bar" .
        }');

        $this->assertEquals(2, \count($this->runSPOQuery()['result']['rows']));

        $this->fixture->query('DELETE {<http://s> <http://p1> ?o .}');

        $this->assertEquals(1, \count($this->runSPOQuery()['result']['rows']));
    }

    public function testDeleteAGraph()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/1> {
            <http://s> <http://p1> "baz" .
        }');

        $this->assertEquals(1, \count($this->runSPOQuery()['result']['rows']));

        $this->fixture->query('DELETE FROM <http://example.com/1>');

        $this->assertEquals(0, \count($this->runSPOQuery()['result']['rows']));
    }

    public function testDeleteWhere()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/1> {
            <http://s> <http://to-delete> 1, 2 .
            <http://s> <http://to-check> 1, 2 .
            <http://s> rdf:type <http://Test> .
        }');

        $this->assertEquals(5, \count($this->runSPOQuery()['result']['rows']));

        $this->fixture->query('DELETE {
            <http://s> <http://to-delete> 1, 2 .
        } WHERE {
            <http://s> <http://to-check> 1, 2 .
        }');

        $this->assertEquals(3, \count($this->runSPOQuery()['result']['rows']));
    }

    public function testDeleteWhereWithBlankNode()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/1> {
            _:a <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://Person> ;
                <http://foo> <http://bar > .
        }');

        $this->assertEquals(2, \count($this->runSPOQuery()['result']['rows']));

        $this->fixture->query('DELETE {
            _:a ?p ?o .
        } WHERE {
            _:a <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://Person> .
        }');

        // first we check the expected behavior and afterwards skip to notice the
        // developer about it.
        $this->assertEquals(2, \count($this->runSPOQuery()['result']['rows']));
        $this->markTestSkipped('DELETE queries with blank nodes are not working.');
    }

    public function testDeleteFromWhere()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/1> {
            <http://s> <http://to-delete> 1, 2 .
            <http://s> <http://to-check> 1, 2 .
            <http://s> rdf:type <http://Test> .
        }');

        $this->assertEquals(5, \count($this->runSPOQuery('http://example.com/1')['result']['rows']));

        $this->fixture->query('DELETE FROM <http://example.com/1> {
            <http://s> <http://to-delete> 1, 2 .
        } WHERE {
            <http://s> <http://to-check> 1, 2 .
        }');

        $this->assertEquals(3, \count($this->runSPOQuery('http://example.com/1')['result']['rows']));
    }
}
