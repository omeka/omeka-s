<?php

namespace Tests\db_adapter_depended\sparql_1_1_tests;

/**
 * Runs W3C tests from https://www.w3.org/2009/sparql/docs/tests/.
 *
 * Version: 2012-10-23 20:52 (sparql11-test-suite-20121023.tar.gz)
 *
 * Tests are located in the w3c-tests folder.
 */
class ConstructTest extends ComplianceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->w3cTestsFolderPath = __DIR__.'/w3c-tests/construct';
        $this->testPref = 'http://www.w3.org/2009/sparql/docs/tests/data-sparql11/construct/manifest#';
    }

    /**
     * Overriden. Helper function to get expected query result.
     *
     * @param string $testUri
     *
     * @return array
     */
    protected function getExpectedResult($testUri)
    {
        $res = $this->store->query('
            PREFIX mf: <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> mf:result ?resultFile .
            }
        ');

        // if no result was given, expect test is of type NegativeSyntaxTest11,
        // which has no data (group-data-X.ttl) and result (.srx) file.
        if (0 < \count($res['result']['rows'])) {
            $parser = \ARC2::getTurtleParser();
            $parser->parse(file_get_contents($res['result']['rows'][0]['resultFile']));

            return $parser->getSimpleIndex();
        } else {
            return null;
        }
    }

    /**
     * Overriden, because expected result is of type turtle and not XML.
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
        }

        return true;
    }

    /*
     * tests
     */

    public function testConstructwhere02()
    {
        $this->assertTrue($this->runTestFor('constructwhere02'));
    }

    public function testConstructwhere03()
    {
        $this->assertTrue($this->runTestFor('constructwhere03'));
    }

    public function testConstructwhere04()
    {
        $this->assertTrue($this->runTestFor('constructwhere04'));
    }

    public function testConstructwhere05()
    {
        $this->assertTrue($this->runTestFor('constructwhere05'));
    }

    public function testConstructwhere06()
    {
        $this->assertTrue($this->runTestFor('constructwhere06'));
    }
}
