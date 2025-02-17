<?php

namespace Tests\db_adapter_depended\sparql_1_1_tests;

/**
 * Runs W3C tests from https://www.w3.org/2009/sparql/docs/tests/.
 *
 * Version: 2012-10-23 20:52 (sparql11-test-suite-20121023.tar.gz)
 *
 * Tests are located in the w3c-tests folder.
 */
class AggregatesTest extends ComplianceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->w3cTestsFolderPath = __DIR__.'/w3c-tests/aggregates';
        $this->testPref = 'http://www.w3.org/2009/sparql/docs/tests/data-sparql11/aggregates/manifest#';
    }

    /*
     * tests
     */

    public function testAggAvg01()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);

        $testname = 'agg-avg-01';

        // get test data
        $data = $this->getTestData($this->testPref.$testname);

        // load test data into graph
        $this->store->insert($data, $this->dataGraphUri);

        // get query to test
        $testQuery = $this->getTestQuery($this->testPref.$testname);

        // get actual result for given test query
        $actualResult = $this->store->query($testQuery);
        $actualResultAsXml = $this->getXmlVersionOfResult($actualResult);

        $this->assertEquals(
            '2',
            (string) $actualResultAsXml->results->result->binding->literal[0]
        );

        // remember current behavior, but skip test anyway to show developer here is still a problem.
        $this->markTestSkipped(
            'Rounding bug in AVG function (MySQL). See https://github.com/semsol/arc2/issues/99'
        );
    }

    public function testAggEmptyGroup()
    {
        $this->assertTrue($this->runTestFor('agg-empty-group'));
    }

    public function testAggMin01()
    {
        $this->markTestSkipped(
            'Skipped, because of known bug that ARC2 \'s Turtle parser can not parse decimals. '
            .'For more information, see #136'
        );

        /*
         * it seems the Turtle parser is not able to detect "1.0", but only "1"
         *
         * see file db_adapter_depended/sparql_1_1_tests/w3c-tests/aggregates/agg-numeric.ttl
         */

        $this->assertTrue($this->runTestFor('agg-min-01'));
    }

    public function testAgg01()
    {
        $this->assertTrue($this->runTestFor('agg01'));
    }

    public function testAgg02()
    {
        $this->assertTrue($this->runTestFor('agg02'));
    }

    public function testAgg04()
    {
        $this->assertTrue($this->runTestFor('agg04'));
    }

    public function testAgg05()
    {
        $this->assertTrue($this->runTestFor('agg05'));
    }

    public function testAgg08()
    {
        $this->assertTrue($this->runTestFor('agg08'));
    }

    public function testAgg09()
    {
        $this->assertTrue($this->runTestFor('agg09'));
    }

    public function testAgg10()
    {
        $this->assertTrue($this->runTestFor('agg10'));
    }

    public function testAgg11()
    {
        $this->assertTrue($this->runTestFor('agg11'));
    }

    public function testAgg12()
    {
        $this->assertTrue($this->runTestFor('agg12'));
    }
}
