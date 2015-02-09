<?php
namespace OmekaTest\Job\Strategy;

use Omeka\Job\Strategy\SynchronousStrategy;
use Omeka\Model\Entity\Job;
use Omeka\Test\TestCase;

class SynchronousStrategyTest extends TestCase
{
    protected $synchronousStrategy;

    public function setUp()
    {
        $this->synchronousStrategy = new SynchronousStrategy;
    }

    public function testSend()
    {
        require OMEKA_PATH . '/application/test/OmekaTest/Job/_files/Job.php';

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLocator = $this->getServiceManager(array(
            'Omeka\EntityManager' => $entityManager,
        ));

        $job = $this->getMock('Omeka\Model\Entity\Job');
        $job->expects($this->once())
            ->method('getClass')
            ->will($this->returnValue('OmekaTest\Job\Job'));
        $job->expects($this->once())
            ->method('getArgs');
        $job->expects($this->exactly(2))
            ->method('setStatus')
            ->withConsecutive(
                array($this->equalTo(Job::STATUS_IN_PROGRESS)),
                array($this->equalTo(Job::STATUS_COMPLETED))
            );
        $job->expects($this->once())
            ->method('setEnded')
            ->with($this->isInstanceOf('DateTime'));

        $this->synchronousStrategy->setServiceLocator($serviceLocator);
        $this->synchronousStrategy->send($job);
    }
}
