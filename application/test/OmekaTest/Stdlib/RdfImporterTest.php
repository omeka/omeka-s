<?php
namespace Omeka\Stdlib;

use Omeka\Test\DbTestCase;

class RdfImporterTest extends DbTestCase
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    public function setUp()
    {
        parent::setUp();

        $services = self::getApplication()->getServiceManager();
        $apiManager = $services->get('Omeka\ApiManager');
        $entityManager = $services->get('Omeka\EntityManager');
        $this->rdfImporter = new RdfImporter($apiManager, $entityManager);
    }

    public function testGetMembers()
    {
        $strategy = 'file';
        $namespaceUri = 'http://localhost.test';
        $options = [
            'format' => 'jsonld',
            'file' => __DIR__ . '/_files/test.json',
        ];
        // Issue in library ml/jsonld for php 7.2
        // @see https://github.com/lanthaler/JsonLD/pull/92
        $members = $this->rdfImporter->getMembers($strategy, $namespaceUri, $options);
        $this->assertNotNull($members);
    }
}
