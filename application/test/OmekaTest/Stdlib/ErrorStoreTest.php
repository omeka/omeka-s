<?php
namespace Omeka\Stdlib;

use Omeka\Test\TestCase;

class ErrorStoreTest extends TestCase
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
        $this->assertEquals([
            'foo' => [
                'foo_message_one',
                'foo_message_two',
            ],
            'bar' => ['bar_message'],
        ], $this->errorStore->getErrors());
    }

    public function testAddsValidatorMessages()
    {
        $validatorMessages = [
            'foo_one' => 'foo_one_message',
            'foo_two' => 'foo_two_message',
        ];
        $this->errorStore->addValidatorMessages('bar', $validatorMessages);
        $this->assertEquals([
            'bar' => [
                'foo_one_message',
                'foo_two_message',
            ],
        ], $this->errorStore->getErrors());
    }

    public function testMergesErrors()
    {
        $errorStore = $this->createMock('Omeka\Stdlib\ErrorStore');
        $errorStore->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue(['foo' => ['foo_message_two']]));
        $this->errorStore->addError('foo', 'foo_message_one');
        $this->errorStore->mergeErrors($errorStore);
        $this->assertEquals([
            'foo' => [
                'foo_message_one',
                'foo_message_two',
            ],
        ], $this->errorStore->getErrors());
    }

    public function testClearsErrors()
    {
        $this->errorStore->addError('foo', 'foo_message');
        $this->errorStore->clearErrors();
        $this->assertEquals([], $this->errorStore->getErrors());
    }

    public function testHasErrors()
    {
        $this->assertFalse($this->errorStore->hasErrors());
        $this->errorStore->addError('foo', 'foo_message');
        $this->assertTrue($this->errorStore->hasErrors());
    }
}
