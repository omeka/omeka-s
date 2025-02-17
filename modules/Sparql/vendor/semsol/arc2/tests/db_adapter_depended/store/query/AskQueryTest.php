<?php

namespace Tests\db_adapter_depended\store\query;

use Tests\ARC2_TestCase;

/**
 * Tests for query method - focus on ASK queries.
 */
class AskQueryTest extends ARC2_TestCase
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

    public function testAskDefaultGraph()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $res = $this->fixture->query('ASK {<http://s> <http://p1> ?o.}');
        $this->assertEquals(
            [
                'query_type' => 'ask',
                'result' => true,
                'query_time' => $res['query_time'],
            ],
            $res
        );
    }

    public function testAskGraphSpecified()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $res = $this->fixture->query('ASK FROM <http://example.com/> {<http://s> <http://p1> ?o.}');
        $this->assertEquals(
            [
                'query_type' => 'ask',
                'result' => true,
                'query_time' => $res['query_time'],
            ],
            $res
        );
    }
}
