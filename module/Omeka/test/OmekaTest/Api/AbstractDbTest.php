<?php
namespace OmekaTest\Api;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Omeka\Api\Adapter\AbstractDb;
use Omeka\Stdlib\ErrorStore;
use Omeka\Model\Entity\AbstractEntity;
use Omeka\Model\Exception as ModelException;

class AbstractDbTest extends \PHPUnit_Framework_TestCase
{
    protected $dbAdapter;

    public function setUp()
    {
        $this->dbAdapter = $this->getMockForAbstractClass(
            'Omeka\Api\Adapter\AbstractDb'
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
