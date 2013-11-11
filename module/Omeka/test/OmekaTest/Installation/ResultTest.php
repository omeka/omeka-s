<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Result;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->result = new Result;
    }

    public function testAddsErrorMessage()
    {
        $taskClass = 'task_class';
        $taskName = 'task_name';
        $message = 'error_message';

        $this->result->setCurrentTask($taskClass, $taskName);
        $this->result->addMessage($message, Result::MESSAGE_TYPE_ERROR);
        $this->assertTrue($this->result->isError());
        $this->assertEquals(array(
            $taskClass => array(
                'task_name' => $taskName,
                'error' => array($message),
            ),
        ), $this->result->getMessages());
    }

    public function testAddsWarningMessage()
    {
        $taskClass = 'task_class';
        $taskName = 'task_name';
        $message = 'warning_message';

        $this->result->setCurrentTask($taskClass, $taskName);
        $this->result->addMessage($message, Result::MESSAGE_TYPE_WARNING);
        $this->assertFalse($this->result->isError());
        $this->assertEquals(array(
            $taskClass => array(
                'task_name' => $taskName,
                'warning' => array($message),
            ),
        ), $this->result->getMessages());
    }

    public function testAddsInfoMessage()
    {
        $taskClass = 'task_class';
        $taskName = 'task_name';
        $message = 'info_message';

        $this->result->setCurrentTask($taskClass, $taskName);
        $this->result->addMessage($message, Result::MESSAGE_TYPE_INFO);
        $this->assertFalse($this->result->isError());
        $this->assertEquals(array(
            $taskClass => array(
                'task_name' => $taskName,
                'info' => array($message),
            ),
        ), $this->result->getMessages());
    }
}
