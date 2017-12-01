<?php
namespace OmekaTest\Job\Strategy;

use Omeka\Job\DispatchStrategy\Synchronous;
use Omeka\Entity\Job;
use Omeka\Test\TestCase;

class SynchronousStrategyTest extends TestCase
{
    protected $synchronousStrategy;

    public function setUp()
    {
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $serviceLocator = $this->getServiceManager([
            'Omeka\EntityManager' => $entityManager,
        ]);
        $synchronousStrategy = new Synchronous($serviceLocator);
        $this->synchronousStrategy = $synchronousStrategy;
    }

    public function testSend()
    {
        require OMEKA_PATH . '/application/test/OmekaTest/Job/_files/Job.php';

        $job = $this->createMock('Omeka\Entity\Job');
        $job->expects($this->once())
            ->method('getClass')
            ->will($this->returnValue('OmekaTest\Job\Job'));
        $job->expects($this->exactly(2))
            ->method('setStatus')
            ->withConsecutive(
                [$this->equalTo(Job::STATUS_IN_PROGRESS)],
                [$this->equalTo(Job::STATUS_COMPLETED)]
            );
        $job->expects($this->once())
            ->method('setEnded')
            ->with($this->isInstanceOf('DateTime'));

        $this->synchronousStrategy->send($job);
    }
}
