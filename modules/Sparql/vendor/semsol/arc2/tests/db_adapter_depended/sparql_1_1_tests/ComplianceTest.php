<?php

namespace Tests\db_adapter_depended\sparql_1_1_tests;

use Tests\ARC2_TestCase;

/**
 * Runs W3C tests from https://www.w3.org/2009/sparql/docs/tests/.
 *
 * Version: 2012-10-23 20:52 (sparql11-test-suite-20121023.tar.gz)
 *
 * Tests are located in the w3c-tests folder.
 */
abstract class ComplianceTest extends ARC2_TestCase
{
    /**
     * @var ARC2_Store
     */
    protected $store;

    /**
     * @var string
     */
    protected $dataGraphUri;

    /**
     * @var string
     */
    protected $manifestGraphUri;

    /**
     * @var string
     */
    protected $testPref;

    /**
     * @var string
     */
    protected $w3cTestsFolderPath;

    protected function setUp(): void
    {
        parent::setUp();

        // set graphs
        $this->dataGraphUri = 'http://arc/data/';
        $this->manifestGraphUri = 'http://arc/manifest/';

        /*
         * Setup a store instance to load test information and data.
         */
        $this->store = \ARC2::getStore($this->dbConfig);
        $this->store->setup();
    }

    protected function tearDown(): void
    {
        $this->store->reset();
        $this->store->closeDBCon();

        parent::tearDown();
    }

    /**
     * Helper function to get expected query result.
     *
     * @param string $testUri
     *
     * @return \SimpleXMLElement instance of \SimpleXMLElement representing the result
     */
    protected function getExpectedResult($testUri)
    {
        /*
            example:

            :group1 mf:result <group01.srx>
         */
        $res = $this->store->query('
            PREFIX mf: <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> mf:result ?resultFile .
            }
        ');

        // if no result was given, expect test is of type NegativeSyntaxTest11,
        // which has no data (group-data-X.ttl) and result (.srx) file.
        if (0 < \count($res['result']['rows'])) {
            return new \SimpleXMLElement(file_get_contents($res['result']['rows'][0]['resultFile']));
        } else {
            return null;
        }
    }

    /**
     * Helper function to get the number of rows in a table.
     *
     * @param string $tableName
     *
     * @return int number of rows in the target table
     */
    protected function getRowCount($tableName)
    {
        $row = $this->store->getDBObject()->fetchRow(
            'SELECT COUNT(*) as count FROM '.$tableName,
            $this->store->getDBCon()
        );

        return $row['count'];
    }

    /**
     * Helper function to load data for a given test.
     *
     * @param string $testUri
     *
     * @return array parsed file content
     */
    protected function getTestData($testUri)
    {
        /*
            example:

            :group1 mf:action [
                qt:data   <group-data-1.ttl>
            ]
         */
        $file = $this->store->query('
            PREFIX mf: <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
            PREFIX qt: <http://www.w3.org/2001/sw/DataAccess/tests/test-query#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> mf:action [ qt:data ?file ] .
            }
        ');

        // if no result was given, expect test is of type NegativeSyntaxTest11,
        // which has no data (group-data-X.ttl) and result (.srx) file.
        if (0 < \count($file['result']['rows'])) {
            $parser = \ARC2::getTurtleParser();
            $parser->parse($file['result']['rows'][0]['file']);

            return $parser->getSimpleIndex();
        } else {
            return null;
        }
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
            PREFIX qt: <http://www.w3.org/2001/sw/DataAccess/tests/test-query#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> mf:action [ qt:query ?queryFile ] .
            }
        ');

        // if test is of type NegativeSyntaxTest11, mf:action points not to a blank node,
        // but directly to the query file.
        if (0 == \count($query['result']['rows'])) {
            $query = $this->store->query('
                PREFIX mf: <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
                SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                    <'.$testUri.'> mf:action ?queryFile .
                }
            ');
        }

        $query = file_get_contents($query['result']['rows'][0]['queryFile']);

        // add data graph information as FROM clause, because ARC2 can't handle default graph
        // queries. for more information see https://github.com/semsol/arc2/issues/72.
        if (str_contains($query, 'ASK')
            || str_contains($query, 'CONSTRUCT')
            || str_contains($query, 'SELECT')) {
            $query = str_replace('WHERE', 'FROM <'.$this->dataGraphUri.'> WHERE', $query);
        }

        return $query;
    }

    /**
     * Helper function to get test type.
     *
     * @param string $testUri
     *
     * @return string Type URI
     */
    protected function getTestType($testUri)
    {
        $type = $this->store->query('
            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> rdf:type ?type .
            }
        ');

        return $type['result']['rows'][0]['type'];
    }

    /**
     * Transforms ARC2 query result to a \SimpleXMLElement instance for later comparison.
     *
     * @return \SimpleXMLElement
     */
    protected function getXmlVersionOfResult(array $result)
    {
        $w = new \XMLWriter();
        $w->openMemory();
        $w->startDocument('1.0');

        // sparql (root element)
        $w->startElement('sparql');
        $w->writeAttribute('xmlns', 'http://www.w3.org/2005/sparql-results#');

        // sparql > head
        $w->startElement('head');

        foreach ($result['result']['variables'] as $var) {
            $w->startElement('variable');
            $w->writeAttribute('name', $var);
            $w->endElement();
        }

        // end sparql > head
        $w->endElement();

        // sparql > results
        $w->startElement('results');

        foreach ($result['result']['rows'] as $row) {
            /*
                example:

                <result>
                  <binding name="s">
                    <uri>http://example/s1</uri>
                  </binding>
                </result>
             */

            // new result element
            $w->startElement('result');

            foreach ($result['result']['variables'] as $var) {
                if (empty($row[$var])) {
                    continue;
                }

                // sparql > results > result > binding
                $w->startElement('binding');
                $w->writeAttribute('name', $var);

                // if a variable type is set
                if (isset($row[$var.' type'])) {
                    // uri
                    if ('uri' == $row[$var.' type']) {
                        // example: <uri>http://example/s1</uri>
                        $w->startElement('uri');
                        $w->text($row[$var]);
                        $w->endElement();
                    } elseif ('literal' == $row[$var.' type']) {
                        // example: <literal datatype="http://www.w3.org/2001/XMLSchema#integer">9</literal>
                        $w->startElement('literal');

                        // its not part of the ARC2 result set, but expected later on
                        if (true === ctype_digit((string) $row[$var])) {
                            $w->writeAttribute('datatype', 'http://www.w3.org/2001/XMLSchema#integer');
                        }

                        $w->text($row[$var]);
                        $w->endElement();
                    }
                }

                // end sparql > results > result > binding
                $w->endElement();
            }

            // end result
            $w->endElement();
        }

        // add <result></result> if no data were found
        if (0 == \count($result['result']['rows'])) {
            $w->startElement('result');
            $w->endElement();
        }

        // end sparql > results
        $w->endElement();

        // end sparql
        $w->endElement();

        return new \SimpleXMLElement($w->outputMemory(true));
    }

    /**
     * Loads manifest.ttl into manifest graph.
     *
     * @param string $folderPath
     */
    protected function loadManifestFileIntoStore($folderPath)
    {
        // parse manifest.ttl and load its content into $this->manifestGraphUri
        $parser = \ARC2::getTurtleParser();
        $parser->parse($folderPath.'/manifest.ttl');
        $this->store->insert($parser->getSimpleIndex(), $this->manifestGraphUri);
    }

    /**
     * @param string $query
     */
    protected function makeQueryA1Liner($query)
    {
        return preg_replace('/\s\s+/', ' ', $query);
    }

    /**
     * Helper function to run a certain test.
     *
     * @param string $testName E.g. group01
     */
    protected function runTestFor($testName)
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);

        // get test type (this determines, if we expect a normal test or one, that must fail)
        $negTestUri = 'http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#NegativeSyntaxTest11';
        $type = $this->getTestType($this->testPref.$testName);

        // test has to FAIL
        if ($negTestUri == $type) {
            // get query to test
            $testQuery = $this->getTestQuery($this->testPref.$testName);

            $this->assertFalse(empty($testQuery), 'Can not test, because test query is empty.');

            $arc2Result = $this->store->query($testQuery);
            if (0 == $arc2Result) {
                $this->assertEquals(0, $arc2Result);
            } elseif (isset($arc2Result['result']['rows'])) {
                $this->assertEquals(0, \count($arc2Result['result']['rows']));
            } else {
                throw new \Exception('Invalid result by query method: '.json_encode($arc2Result));
            }

            // test has to be SUCCESSFUL
        } else {
            // get test data
            $data = $this->getTestData($this->testPref.$testName);

            // load test data into graph
            $this->store->insert($data, $this->dataGraphUri);

            // get query to test
            $testQuery = $this->getTestQuery($this->testPref.$testName);

            // get expected result
            $expectedResult = $this->getExpectedResult($this->testPref.$testName);

            // get actual result for given test query
            $actualResult = $this->store->query($testQuery);
            $actualResultAsXml = $this->getXmlVersionOfResult($actualResult);

            $this->assertEquals($expectedResult, $actualResultAsXml);
        }

        return true;
    }
}
