<?php
namespace OmekaTest\Model;

use Omeka\Entity\Job;
use Omeka\Test\TestCase;

class JobTest extends TestCase
{
    protected $job;

    public function setUp()
    {
        $this->job = new Job;
    }

    public function testInitialState()
    {
        $this->assertNull($this->job->getId());
        $this->assertNull($this->job->getPid());
        $this->assertNull($this->job->getStatus());
        $this->assertNull($this->job->getClass());
        $this->assertNull($this->job->getArgs());
        $this->assertNull($this->job->getOwner());
        $this->assertNull($this->job->getStarted());
        $this->assertNull($this->job->getEnded());
    }

    public function testSetPid()
    {
        $pid = 'test-pid';
        $this->job->setPid($pid);
        $this->assertEquals($pid, $this->job->getPid());
    }

    public function testSetStatus()
    {
        $status = 'test-status';
        $this->job->setStatus($status);
        $this->assertEquals($status, $this->job->getStatus());
    }

    public function testSetClass()
    {
        $class = 'test-class';
        $this->job->setClass($class);
        $this->assertEquals($class, $this->job->getClass());
    }

    public function testSetArgs()
    {
        $args = 'test-args';
        $this->job->setArgs($args);
        $this->assertEquals($args, $this->job->getArgs());
    }

    public function testSetOwner()
    {
        $owner = $this->createMock('Omeka\Entity\User');
        $this->job->setOwner($owner);
        $this->assertEquals($owner, $this->job->getOwner());
    }

    public function testSetStarted()
    {
        $dateTime = $this->createMock('DateTime');
        $this->job->setStarted($dateTime);
        $this->assertSame($dateTime, $this->job->getStarted());
    }

    public function testSetEnded()
    {
        $dateTime = $this->createMock('DateTime');
        $this->job->setEnded($dateTime);
        $this->assertSame($dateTime, $this->job->getEnded());
    }

    public function testPrePersist()
    {
        $lifecycleEventArgs = $this
            ->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $this->job->prePersist($lifecycleEventArgs);
        $this->assertInstanceOf('DateTime', $this->job->getStarted());
    }
}
