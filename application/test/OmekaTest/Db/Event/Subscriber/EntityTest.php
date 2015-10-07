<?php
namespace OmekaTest\Db\Type;

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
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $services = $this->getServiceManager([
            'EventManager' => $eventManager,
        ]);
        $entity = new Entity($services);
        $this->assertEquals(
            $this->subscribedEvents,
            $entity->getSubscribedEvents()
        );
    }

    public function testCallbacks()
    {
        $eventManager = $this->getMock('Zend\EventManager\EventManager');
        $eventManager->expects($this->exactly(6))
            ->method('setIdentifiers')
            ->with($this->equalTo(['Omeka\Db\Event\Subscriber\Entity']));
        $eventManager->expects($this->exactly(6))
            ->method('trigger')
            ->with($this->isInstanceOf('Omeka\Event\Event'));
        $services = $this->getServiceManager([
            'EventManager' => $eventManager,
        ]);
        $entity = new Entity($services);

        $eventArgs = $this->getMockBuilder('Doctrine\Common\Persistence\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        foreach ($this->subscribedEvents as $callback) {
            $entity->$callback($eventArgs);
        }
    }
}
