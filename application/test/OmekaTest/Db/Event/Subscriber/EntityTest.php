<?php
namespace OmekaTest\Db\Event\Subscriber;

use Omeka\Db\Event\Subscriber\Entity;
use Omeka\Test\TestCase;

class EntityTest extends TestCase
{
    protected $subscribedEvents = [
        'preRemove', 'postRemove',
        'prePersist', 'postPersist',
        'preUpdate', 'postUpdate',
    ];

    public function testGetSubscribedEvents()
    {
        $eventManager = $this->createMock('Zend\EventManager\EventManager');
        $entity = new Entity($eventManager);
        $this->assertEquals(
            $this->subscribedEvents,
            $entity->getSubscribedEvents()
        );
    }

    public function testCallbacks()
    {
        $eventManager = $this->createMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->exactly(6))
            ->method('setIdentifiers')
            ->with($this->equalTo(['Omeka\Db\Event\Subscriber\Entity']));
        $eventManager->expects($this->exactly(6))
            ->method('triggerEvent')
            ->with($this->isInstanceOf('Zend\EventManager\Event'));
        $entity = new Entity($eventManager);

        $eventArgs = $this->getMockBuilder('Doctrine\Common\Persistence\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $eventArgs->expects($this->any())
            ->method('getEntity')
            ->willReturn($entity);
        foreach ($this->subscribedEvents as $callback) {
            $entity->$callback($eventArgs);
        }
    }
}
