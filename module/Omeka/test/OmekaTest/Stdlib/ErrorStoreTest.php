<?php
namespace Omeka\Stdlib;

use Omeka\Stdlib\ErrorStore;

class ErrorStoreTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->errorStore = new ErrorStore;
    }

    public function testAddsAndGetsErrors()
    {
        $this->errorStore->addError('foo', 'foo_message_one');
        $this->errorStore->addError('foo', 'foo_message_two');
        $this->errorStore->addError('bar', 'bar_message');
        $this->assertEquals(array(
            'foo' => array(
                'foo_message_one',
                'foo_message_two',
            ),
            'bar' => array('bar_message'),
        ), $this->errorStore->getErrors());
    }

    public function testAddsValidatorMessages()
    {
        $validatorMessages = array(
            'foo_one' => 'foo_one_message',
            'foo_two' => 'foo_two_message',
        );
        $this->errorStore->addValidatorMessages('bar', $validatorMessages);
        $this->assertEquals(array(
            'bar' => array(
                'foo_one_message',
                'foo_two_message',
            )
        ), $this->errorStore->getErrors());
    }

    public function testClearsErrors()
    {
        $this->errorStore->addError('foo', 'foo_message');
        $this->errorStore->clearErrors();
        $this->assertEquals(array(), $this->errorStore->getErrors());
    }

    public function testHasErrors()
    {
        $this->assertFalse($this->errorStore->hasErrors());
        $this->errorStore->addError('foo', 'foo_message');
        $this->assertTrue($this->errorStore->hasErrors());
    }
}
