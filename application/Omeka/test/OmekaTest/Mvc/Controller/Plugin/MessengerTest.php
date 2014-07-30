<?php
namespace OmekaTest\Mvc\Controller\Plugin;

use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Test\TestCase;

class MessengerTest extends TestCase
{
    public function testAdd()
    {
        $messenger = new Messenger;
        $messenger->add(Messenger::ERROR, 'test-error-one');
        $messenger->add(Messenger::ERROR, 'test-error-two');
        $messenger->add(Messenger::SUCCESS, 'test-success-one');
        $messenger->add(Messenger::SUCCESS, 'test-success-two');
        $messenger->add(Messenger::WARNING, 'test-warning-one');
        $messenger->add(Messenger::WARNING, 'test-warning-two');

        $messages = $messenger->get();
        $this->assertEquals(array(
            0 => array('test-error-one', 'test-error-two'),
            1 => array('test-success-one', 'test-success-two'),
            2 => array('test-warning-one', 'test-warning-two'),
        ), $messages);

        // Must clear to avoid message bleed-over in subsequent tests.
        $messenger->clear();
    }

    public function testAddError()
    {
        $messenger = new Messenger;
        $messenger->addError('test-error');

        $messages = $messenger->get();
        $this->assertEquals(array(
            Messenger::ERROR => array('test-error'),
        ), $messages);

        $messenger->clear();
    }

    public function testAddSuccess()
    {
        $messenger = new Messenger;
        $messenger->addSuccess('test-success');

        $messages = $messenger->get();
        $this->assertEquals(array(
            Messenger::SUCCESS => array('test-success'),
        ), $messages);

        $messenger->clear();
    }

    public function testAddWarning()
    {
        $messenger = new Messenger;
        $messenger->addWarning('test-warning');

        $messages = $messenger->get();
        $this->assertEquals(array(
            Messenger::WARNING => array('test-warning'),
        ), $messages);

        $messenger->clear();
    }
}
