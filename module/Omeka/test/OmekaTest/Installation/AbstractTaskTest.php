<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Result;

class AbstractTaskTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorSetsResult()
    {
        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->once())
            ->method('setCurrentTask');
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($result)
        );
        $this->assertInstanceOf('Omeka\Installation\Result', $task->getResult());
    }

    public function testAddsError()
    {
        $message = 'error_message';

        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->once())
            ->method('addMessage')
            ->with($message, Result::MESSAGE_TYPE_ERROR);
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($result)
        );

        $task->addError($message);
    }

    public function testAddsErrorStore()
    {
        $message = 'error_message';

        $errorStore = $this->getMock('Omeka\Stdlib\ErrorStore');
        $errorStore->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue(array(
                'key' => array($message, $message),
            )));
        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->exactly(2))
            ->method('addMessage')
            ->with($message, Result::MESSAGE_TYPE_ERROR);
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($result)
        );

        $task->addErrorStore($errorStore);
    }

    public function testAddsWarning()
    {
        $message = 'warning_message';

        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->once())
            ->method('addMessage')
            ->with($message, Result::MESSAGE_TYPE_WARNING);
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($result)
        );

        $task->addWarning($message);
    }

    public function testAddsInfo()
    {
        $message = 'info_message';

        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->once())
            ->method('addMessage')
            ->with($message, Result::MESSAGE_TYPE_INFO);
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($result)
        );

        $task->addInfo($message);
    }
}
