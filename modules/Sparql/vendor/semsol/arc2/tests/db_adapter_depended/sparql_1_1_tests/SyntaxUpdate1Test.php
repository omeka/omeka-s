<?php

namespace Tests\db_adapter_depended\sparql_1_1_tests;

/**
 * Runs W3C tests from https://www.w3.org/2009/sparql/docs/tests/.
 *
 * Version: 2012-10-23 20:52 (sparql11-test-suite-20121023.tar.gz)
 *
 * Tests are located in the w3c-tests folder.
 */
class SyntaxUpdate1Test extends ComplianceTest
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->w3cTestsFolderPath = __DIR__.'/w3c-tests/syntax-update-1';
        $this->testPref = 'http://www.w3.org/2009/sparql/docs/tests/data-sparql11/syntax-update-1/manifest#';
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

            :test_1 rdf:type   mf:PositiveUpdateSyntaxTest11 ;
               dawgt:approval dawgt:Approved ;
               dawgt:approvedBy <http://www.w3.org/2009/sparql/meeting/2011-04-05#resolution_2> ;
               mf:name    "syntax-update-01.ru" ;
               mf:action  <syntax-update-01.ru> ;.
         */
        $query = $this->store->query('
            PREFIX mf: <http://www.w3.org/2001/sw/DataAccess/tests/test-manifest#> .
            SELECT * FROM <'.$this->manifestGraphUri.'> WHERE {
                <'.$testUri.'> mf:action ?queryFile .
            }
        ');

        return file_get_contents($query['result']['rows'][0]['queryFile']);
    }

    /*
     * tests
     */

    public function testTest1()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_1');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertTrue(\is_array($result) && isset($result['query_type']));
    }

    public function testTest2()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_2');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertTrue(\is_array($result) && isset($result['query_type']));
    }

    public function testTest41()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_41');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest42()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_42');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest43()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_43');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest44()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_44');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest45()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_45');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest46()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_46');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest47()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_47');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest48()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_48');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest49()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_49');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest50()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_50');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest51()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_51');

        // fire query
        $result = $this->store->query($query);

        // check current reaction of ARC2, for compatible reasons
        $this->assertTrue(\is_array($result));

        // check result
        $this->markTestSkipped(
            'Query has to fail, but ARC2 returns an array as if query is considered valid. Query: '
            .\PHP_EOL
            .$this->makeQueryA1Liner($query)
        );
    }

    public function testTest52()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_52');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }

    public function testTest54()
    {
        $this->loadManifestFileIntoStore($this->w3cTestsFolderPath);
        $query = $this->getTestQuery($this->testPref.'test_54');

        // fire query
        $result = $this->store->query($query);

        // check result
        $this->assertEquals(0, $result);
    }
}
