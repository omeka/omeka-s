<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\Resource;

class ResourceTest extends \PHPUnit_Framework_TestCase
{
    protected $resource;

    public function setUp()
    {
        $this->resource = $this->getMockForAbstractClass('Omeka\Model\Entity\Resource');
    }

    public function testInitialState()
    {
        $this->assertNull($this->resource->getId());
        $this->assertNull($this->resource->getOwner());
        $this->assertNull($this->resource->getResourceClass());
        $this->assertInstanceOf(
            '\Doctrine\Common\Collections\ArrayCollection',
            $this->resource->getSites()
        );
    }

    public function testSetState()
    {
        $this->resource->setOwner('owner');
        $this->resource->setResourceClass('resource_class');
        $this->assertEquals('owner', $this->resource->getOwner());
        $this->assertEquals('resource_class', $this->resource->getResourceClass());
    }

    public function testSetsDefaultResourceClass()
    {
        $resourceClassEntity = 'foo';

        // EntityRepository
        $entityRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepository->expects($this->once())
            ->method('findOneBy')
            ->with($this->callback(function ($subject) {
                if (!is_array($subject)) return false;
                if (!isset($subject['resourceType'])) return false;
                if (!isset($subject['isDefault'])) return false;
                if (true !== $subject['isDefault']) return false;
                return true;
            }))
            ->will($this->returnValue($resourceClassEntity));

        // EntityManager
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with($this->equalTo('Omeka\Model\Entity\ResourceClass'))
            ->will($this->returnValue($entityRepository));

        // LifecycleEventArgs
        $eventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $eventArgs->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $this->resource->setDefaultResourceClass($eventArgs);
        $this->assertEquals('foo', $this->resource->getResourceClass());
    }
}
