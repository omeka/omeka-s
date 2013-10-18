<?php
namespace OmekaTest\Api;

class AbstractEntityAdapterTest extends \PHPUnit_Framework_TestCase
{
    protected $dbAdapter;

    public function setUp()
    {
        $this->dbAdapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\Entity\AbstractEntityAdapter'
        );
    }

    public function testSearches()
    {
    }

    public function testCreates()
    {
    }

    public function testCreateHandlesValidationError()
    {
    }

    public function testReads()
    {
    }

    public function testReadHandlesNotFoundError()
    {
    }

    public function testUpdates()
    {
    }

    public function testUpdateHandlesNotFoundErrors()
    {
    }

    public function testUpdateHandlesValidationErrors()
    {
    }

    public function testDeletes()
    {
    }

    public function testDeleteHandlesNotFoundErrors()
    {
    }
}
