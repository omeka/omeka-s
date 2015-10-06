<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\Messages;

class MessagesTest extends TestCase
{
    public function testGet()
    {
        $messages = new Messages;
        $this->assertEquals([], $messages->get());
    }

    public function testInvoke()
    {
        $messages = new Messages;
        $this->assertEquals('', $messages->__invoke());
    }
}
