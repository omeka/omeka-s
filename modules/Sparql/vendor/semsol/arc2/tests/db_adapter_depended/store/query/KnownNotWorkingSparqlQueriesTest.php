<?php

namespace Tests\db_adapter_depended\store\query;

use Tests\ARC2_TestCase;

/**
 * Tests for query method - focus on queries which are known to fail.
 */
class KnownNotWorkingSparqlQueriesTest extends ARC2_TestCase
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

    /**
     * Variable alias.
     */
    public function testSelectAlias()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $res = $this->fixture->query('
            SELECT (?s AS ?s_alias) ?o FROM <http://example.com/> WHERE {?s <http://p1> ?o.}
        ');

        $this->assertEquals(0, $res);
    }

    /**
     * FILTER: langMatches with *.
     *
     * Based on the specification (https://www.w3.org/TR/rdf-sparql-query/#func-langMatches)
     * langMatches with * has to return all entries with no language set.
     */
    public function testSelectFilterLangMatchesWithStar()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "foo" .
            <http://s> <http://p1> "in de"@de .
            <http://s> <http://p1> "in en"@en .
        }');

        $res = $this->fixture->query('
            SELECT ?s ?o WHERE {
                ?s <http://p1> ?o .
                FILTER langMatches (lang(?o), "*")
            }
        ');
        $this->assertEquals(
            [
                'query_type' => 'select',
                'result' => [
                    'variables' => [
                        's', 'o',
                    ],
                    'rows' => [],
                ],
                'query_time' => $res['query_time'],
            ],
            $res
        );
    }

    /**
     * sameTerm.
     */
    public function testSelectSameTerm()
    {
        $this->markTestSkipped(
            'ARC2: solving sameterm does not work properly. The result contains elements multiple times. '
            .\PHP_EOL.'Expected behavior is described here: https://www.w3.org/TR/rdf-sparql-query/#func-sameTerm'
        );

        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://container1> <http://weight> "100" .
            <http://container2> <http://weight> "100" .
        }');

        $res = $this->fixture->query('SELECT ?c1 ?c2 WHERE {
            ?c1 ?weight ?w1.

            ?c2 ?weight ?w2.

            FILTER (sameTerm(?w1, ?w2))
        }');
        $this->assertEquals(
            [
                'query_type' => 'select',
                'result' => [
                    'variables' => [
                        'c1', 'c2',
                    ],
                    'rows' => [
                        [
                            'c1' => 'http://container1',
                            'c1 type' => 'uri',
                            'c2' => 'http://container1',
                            'c2 type' => 'uri',
                        ],
                        [
                            'c1' => 'http://container1',
                            'c1 type' => 'uri',
                            'c2' => 'http://container2',
                            'c2 type' => 'uri',
                        ],
                        [
                            'c1' => 'http://container2',
                            'c1 type' => 'uri',
                            'c2' => 'http://container1',
                            'c2 type' => 'uri',
                        ],
                        [
                            'c1' => 'http://container2',
                            'c1 type' => 'uri',
                            'c2' => 'http://container2',
                            'c2 type' => 'uri',
                        ],
                    ],
                ],
                'query_time' => $res['query_time'],
            ],
            $res,
            '',
            0,
            10,
            true
        );
    }

    /**
     * Sub Select.
     */
    public function testSelectSubSelect()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://person1> <http://id> "1" .
            <http://person3> <http://id> "3" .
            <http://person2> <http://id> "2" .

            <http://person1> <http://knows> <http://person2> .
            <http://person2> <http://knows> <http://person3> .
            <http://person3> <http://knows> <http://person2> .
        }');

        $res = $this->fixture->query('
            SELECT * WHERE {
                {
                    SELECT ?p WHERE {
                        ?p <http://id> "1" .
                    }
                }
                ?p <http://knows> ?who .
            }
        ');

        $this->assertEquals(0, $res);
    }
}
