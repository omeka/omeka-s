<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\Messenger;

class MessengerTest extends TestCase
{
    public function testGet()
    {
        $messages = new Messenger;
        $this->assertEquals(array(), $messages->get());
    }
}
