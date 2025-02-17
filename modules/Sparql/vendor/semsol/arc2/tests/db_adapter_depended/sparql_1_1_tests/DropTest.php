<?php

namespace Tests\db_adapter_depended\sparql_1_1_tests;

/**
 * Runs tests which are based on W3C tests from https://www.w3.org/2009/sparql/docs/tests/.
 *
 * Version: 2012-10-23 20:52 (sparql11-test-suite-20121023.tar.gz)
 *
 * Tests are located in the w3c-tests folder.
 */
class DropTest extends ComplianceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->w3cTestsFolderPath = __DIR__.'/w3c-tests/drop';
        $this->testPref = 'http://www.w3.org/2009/sparql/docs/tests/data-sparql11/drop/manifest#';
    }

    /**
     * Helper function to get test query for a given test.
     *
     * @param string $testUri
     *
     * @return string query to test
     */
    protected function getTestQuery($testUri)
    {
        /*
            example:

            :group1 mf:action [
                qt:query  <group01.rq>
            ]
         */
        $query = $this->store->query('
            PREFIX mf: <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
            PREFIX ut: <http://www.w3.org/2009/sparql/tests/test-update#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> mf:action [ ut:request ?queryFile ] .
            }
        ');

        return $query['result']['rows'][0]['queryFile'];
    }

    /*
     * tests
     */

    // this test is not part of the W3C test collection
    // it tests DELETE FROM <...> command which is the ARC2 equivalent to DROP GRAPH <...>
    public function testDeleteGraph()
    {
        $graphUri = 'http://example.org/g1';

        $this->store->query('INSERT INTO <'.$graphUri.'> {
            <http://example.org/g1> <http://example.org/name> "G1" ;
                                    <http://example.org/description> "Graph 1" .
        }');

        // check if graph really contains data
        $res = $this->store->query('SELECT * WHERE {?s ?p ?o.}');
        $this->assertTrue(0 < \count($res['result']['rows']), 'No test data in graph found.');

        // run test query
        $res = $this->store->query('DELETE FROM <'.$graphUri.'>');

        // check if test data are still available
        $res = $this->store->query('SELECT * FROM <'.$graphUri.'> WHERE {?s ?p ?o.}');
        $this->assertTrue(0 == \count($res['result']['rows']));
    }
}
