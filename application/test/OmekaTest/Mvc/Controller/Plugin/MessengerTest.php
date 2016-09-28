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
        $messenger->add(Messenger::NOTICE, 'test-notice-one');
        $messenger->add(Messenger::NOTICE, 'test-notice-two');

        $messages = $messenger->get();
        $this->assertEquals([
            0 => ['test-error-one', 'test-error-two'],
            1 => ['test-success-one', 'test-success-two'],
            2 => ['test-warning-one', 'test-warning-two'],
            3 => ['test-notice-one', 'test-notice-two'],
        ], $messages);

        // Must clear to avoid message bleed-over in subsequent tests.
        $messenger->clear();
    }

    public function testAddError()
    {
        $messenger = new Messenger;
        $messenger->addError('test-error');

        $messages = $messenger->get();
        $this->assertEquals([
            Messenger::ERROR => ['test-error'],
        ], $messages);

        $messenger->clear();
    }

    public function testAddSuccess()
    {
        $messenger = new Messenger;
        $messenger->addSuccess('test-success');

        $messages = $messenger->get();
        $this->assertEquals([
            Messenger::SUCCESS => ['test-success'],
        ], $messages);

        $messenger->clear();
    }

    public function testAddWarning()
    {
        $messenger = new Messenger;
        $messenger->addWarning('test-warning');

        $messages = $messenger->get();
        $this->assertEquals([
            Messenger::WARNING => ['test-warning'],
        ], $messages);

        $messenger->clear();
    }

    public function testAddNotice()
    {
        $messenger = new Messenger;
        $messenger->addNotice('test-notice');

        $messages = $messenger->get();
        $this->assertEquals([
            Messenger::NOTICE => ['test-notice'],
        ], $messages);

        $messenger->clear();
    }
}
