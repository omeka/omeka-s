<?php

namespace Tests\db_adapter_depended\store\query;

use Tests\ARC2_TestCase;

/**
 * Tests for query method - focus on INSERT INTO queries.
 */
class InsertIntoQueryTest extends ARC2_TestCase
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

    public function testInsertInto()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "baz" .
        }');

        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');
        $this->assertEquals(1, \count($res['result']['rows']));
    }

    public function testInsertIntoAllKindsOfTriples()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> <http://o> .
            <#make> <#me> <#happy> .
            <http://s2> rdf:type <http://Person> .
            <http://s2> <http://foo> 1 .
            <http://s2> <http://foo> 2.0 .
            <http://s2> <http://foo> "3" .
            <http://s2> <http://foo> "4"^^xsd:integer .
            <http://s2> <http://foo> "5"@en .
            _:foo <http://foo> "6" .
        }');

        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');

        // using <#foo> in query makes ARC2 using the phpunit path as prefix
        // e.g. file:///var/www/html/pier-and-peer/ARC2/vendor/phpunit/phpunit/phpunit#
        // therefore we build this prefix manually to check later
        $filePrefix = 'file://'.str_replace('tests/db_adapter_depended/store/query', '', __DIR__);
        $filePrefix .= 'vendor/bin/phpunit#';

        $this->assertEquals(
            [
                [
                    's' => 'http://s',
                    's type' => 'uri',
                    'p' => 'http://p1',
                    'p type' => 'uri',
                    'o' => 'http://o',
                    'o type' => 'uri',
                ],
                [
                    's' => $filePrefix.'make',
                    's type' => 'uri',
                    'p' => $filePrefix.'me',
                    'p type' => 'uri',
                    'o' => $filePrefix.'happy',
                    'o type' => 'uri',
                ],
                [
                    's' => 'http://s2',
                    's type' => 'uri',
                    'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                    'p type' => 'uri',
                    'o' => 'http://Person',
                    'o type' => 'uri',
                ],
                [
                    's' => 'http://s2',
                    's type' => 'uri',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => '1',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
                [
                    's' => 'http://s2',
                    's type' => 'uri',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => '2.0',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#decimal',
                ],
                [
                    's' => 'http://s2',
                    's type' => 'uri',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => '3',
                    'o type' => 'literal',
                ],
                [
                    's' => 'http://s2',
                    's type' => 'uri',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => '4',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
                [
                    's' => 'http://s2',
                    's type' => 'uri',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => '5',
                    'o type' => 'literal',
                    'o lang' => 'en',
                ],
                [
                    's' => $res['result']['rows'][8]['s'],
                    's type' => 'bnode',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => '6',
                    'o type' => 'literal',
                ],
            ],
            $res['result']['rows']
        );
    }

    public function testInsertIntoBlankNode()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> [
                <http://foo> <http://bar>
            ] .
        }');

        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');

        // because bnode ID is random, we check only its structure
        $this->assertTrue(isset($res['result']['rows'][0]));
        $this->assertEquals(1, preg_match('/_:[a-z0-9]+_[a-z0-9]+/', $res['result']['rows'][0]['o']));

        $this->assertEquals(
            [
                [
                    's' => 'http://s',
                    's type' => 'uri',
                    'p' => 'http://p1',
                    'p type' => 'uri',
                    'o' => $res['result']['rows'][0]['o'],
                    'o type' => 'bnode',
                ],
                [
                    's' => $res['result']['rows'][0]['o'],
                    's type' => 'bnode',
                    'p' => 'http://foo',
                    'p type' => 'uri',
                    'o' => 'http://bar',
                    'o type' => 'uri',
                ],
            ],
            $res['result']['rows']
        );
    }

    public function testInsertIntoDate()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> "2009-05-28T18:03:38+09:00" .
            <http://s> <http://p1> "2009-05-28T18:03:38+09:00GMT" .
            <http://s> <http://p1> "21 August 2007" .
        }');

        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');

        $this->assertEquals(
            [
                'query_type' => 'select',
                'result' => [
                    'variables' => ['s', 'p', 'o'],
                    'rows' => [
                        [
                            's' => 'http://s',
                            's type' => 'uri',
                            'p' => 'http://p1',
                            'p type' => 'uri',
                            'o' => '2009-05-28T18:03:38+09:00',
                            'o type' => 'literal',
                        ],
                        [
                            's' => 'http://s',
                            's type' => 'uri',
                            'p' => 'http://p1',
                            'p type' => 'uri',
                            'o' => '2009-05-28T18:03:38+09:00GMT',
                            'o type' => 'literal',
                        ],
                        [
                            's' => 'http://s',
                            's type' => 'uri',
                            'p' => 'http://p1',
                            'p type' => 'uri',
                            'o' => '21 August 2007',
                            'o type' => 'literal',
                        ],
                    ],
                ],
                'query_time' => $res['query_time'],
            ],
            $res
        );
    }

    public function testInsertIntoList()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            <http://s> <http://p1> 1, 2, 3 .
        }');

        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');

        $this->assertEquals(
            [
                [
                    's' => 'http://s',
                    's type' => 'uri',
                    'p' => 'http://p1',
                    'p type' => 'uri',
                    'o' => '1',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
                [
                    's' => 'http://s',
                    's type' => 'uri',
                    'p' => 'http://p1',
                    'p type' => 'uri',
                    'o' => '2',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
                [
                    's' => 'http://s',
                    's type' => 'uri',
                    'p' => 'http://p1',
                    'p type' => 'uri',
                    'o' => '3',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
            ],
            $res['result']['rows']
        );
    }

    // show that ARC2 can't store long values
    public function testInsertIntoLongValue()
    {
        // create long URI (ca. 250 chars)
        $longURI = 'http://'.hash('sha512', 'long')
            .hash('sha512', 'URI');

        // test data
        $this->fixture->query('INSERT INTO <http://graph> {
            <'.$longURI.'/s> <'.$longURI.'/p> <'.$longURI.'/o> ;
                             <'.$longURI.'/p2> <'.$longURI.'/o2> .
        ');

        $res = $this->fixture->query('SELECT * {?s ?p ?o.}');
        $this->assertEquals(
            [
                'query_type' => 'select',
                'result' => [
                    'variables' => ['s', 'p', 'o'],
                    'rows' => [],
                ],
                'query_time' => $res['query_time'],
            ],
            $res
        );

        $this->markTestSkipped('ARC2 can not store long values, e.g. URIs with around 250 chars.');
    }

    public function testInsertIntoListMoreComplex()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> {
            _:b0  rdf:first  1 ;
                  rdf:rest   _:b1 .
            _:b1  rdf:first  ?x ;
                  rdf:rest   _:b2 .
            _:b2  rdf:first  3 ;
                  rdf:rest   rdf:nil .
        }');

        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');

        $this->assertEquals(
            [
                [
                    's' => $res['result']['rows'][0]['s'],
                    's type' => 'bnode',
                    'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#first',
                    'p type' => 'uri',
                    'o' => '1',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
                [
                    's' => $res['result']['rows'][1]['s'],
                    's type' => 'bnode',
                    'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest',
                    'p type' => 'uri',
                    'o' => $res['result']['rows'][1]['o'],
                    'o type' => 'bnode',
                ],
                [
                    's' => $res['result']['rows'][2]['s'],
                    's type' => 'bnode',
                    'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest',
                    'p type' => 'uri',
                    'o' => $res['result']['rows'][2]['o'],
                    'o type' => 'bnode',
                ],
                [
                    's' => $res['result']['rows'][3]['s'],
                    's type' => 'bnode',
                    'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#first',
                    'p type' => 'uri',
                    'o' => '3',
                    'o type' => 'literal',
                    'o datatype' => 'http://www.w3.org/2001/XMLSchema#integer',
                ],
                [
                    's' => $res['result']['rows'][4]['s'],
                    's type' => 'bnode',
                    'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest',
                    'p type' => 'uri',
                    'o' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil',
                    'o type' => 'uri',
                ],
            ],
            $res['result']['rows']
        );
    }

    public function testInsertIntoWhere()
    {
        // test data
        $this->fixture->query('INSERT INTO <http://example.com/> CONSTRUCT {
            <http://baz> <http://location> "Leipzig" .
            <http://baz2> <http://location> "Grimma" .
        } WHERE {
            ?s <http://location> "Leipzig" .
        }');

        // we expect that 1 element gets added to the store, because of the WHERE clause.
        // but ARC2 added none.
        $res = $this->fixture->query('SELECT * FROM <http://example.com/> {?s ?p ?o.}');
        $this->assertEquals(0, \count($res['result']['rows']));

        $this->markTestSkipped(
            'ARC2 does not check the WHERE clause when inserting data. No data added at all.'
            .\PHP_EOL
            .\PHP_EOL.'FYI: https://www.w3.org/Submission/SPARQL-Update/#sec_examples and '
            .\PHP_EOL.'https://github.com/semsol/arc2/wiki/SPARQL-#insert-example'
        );
    }
}
