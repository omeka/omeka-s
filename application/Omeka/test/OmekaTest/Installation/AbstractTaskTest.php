<?php
namespace OmekaTest\Installation;

use Omeka\Installation\Result;
use Omeka\Test\MockBuilder;

class AbstractTaskTest extends \PHPUnit_Framework_TestCase
{
    protected $serviceManager;

    public function setUp()
    {
        $mockBuilder = new MockBuilder;
        $serviceManager = $mockBuilder->getServiceManager(array(
            'MvcTranslator' => $this->getMock('Zend\I18n\Translator\Translator'),
        ));
        $this->serviceManager = $serviceManager;
    }
    
    public function testConstructorSetsResult()
    {
        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->once())
            ->method('setCurrentTask');
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($this->serviceManager, $result)
        );
        $this->assertInstanceOf('Omeka\Installation\Result', $task->getResult());
    }

    public function testSetsAndGetsVar()
    {
        $vars = array('foo' => 'bar');

        $result = $this->getMock('Omeka\Installation\Result');
        $result->expects($this->once())
            ->method('setCurrentTask');
        $task = $this->getMockForAbstractClass(
            'Omeka\Installation\Task\AbstractTask',
            array($this->serviceManager, $result)
        );

        $task->setVars($vars);
        $this->assertEquals($task->getVar('foo'), 'bar');
        $this->assertNull($task->getVar('baz'));
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
            array($this->serviceManager, $result)
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
            array($this->serviceManager, $result)
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
            array($this->serviceManager, $result)
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
            array($this->serviceManager, $result)
        );

        $task->addInfo($message);
    }
}
