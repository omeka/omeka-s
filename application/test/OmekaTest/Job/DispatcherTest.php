<?php
namespace OmekaTest\Job;

use Omeka\Model\Entity\Job;
use Omeka\Job\Dispatcher;
use Omeka\Test\TestCase;

class DispatcherTest extends TestCase
{
    protected $dispatcher;

    public function setUp()
    {
        $strategy = $this->getMock(
            'Omeka\Job\Strategy\StrategyInterface',
            array('send', 'setServiceLocator', 'getServiceLocator')
        );
        $this->dispatcher = new Dispatcher($strategy);
    }

    public function testGetDispatchStrategy()
    {
        $this->assertInstanceOf(
            'Omeka\Job\Strategy\StrategyInterface',
            $this->dispatcher->getDispatchStrategy()
        );
    }

    public function testSetServiceLocator()
    {
        $serviceLocator = $this->getServiceManager();
        $this->dispatcher->setServiceLocator($serviceLocator);
        $this->assertSame($serviceLocator, $this->dispatcher->getServiceLocator());
    }

    public function testDispatch()
    {
        $owner = $this->getMock('Omeka\Model\Entity\User');

        $auth = $this->getMock('Zend\Authentication\AuthenticationService');
        $auth->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($owner));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf('Omeka\Model\Entity\Job'));
        $entityManager->expects($this->once())
            ->method('flush');

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\AuthenticationService' => $auth,
            'Omeka\EntityManager' => $entityManager,
        ));

        $this->dispatcher->setServiceLocator($serviceLocator);
        $this->dispatcher->getDispatchStrategy()->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('Omeka\Model\Entity\Job'));

        $class = 'Omeka\Job\AbstractJob';
        $args = array('foo' => 'bar');

        $job = $this->dispatcher->dispatch($class, $args);

        $this->assertInstanceOf('Omeka\Model\Entity\Job', $job);
        $this->assertEquals(Job::STATUS_STARTING, $job->getStatus());
        $this->assertEquals($class, $job->getClass());
        $this->assertEquals($args, $job->getArgs());
        $this->assertInstanceOf('Omeka\Model\Entity\User', $job->getOwner());
    }
}
