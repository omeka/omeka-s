<?php

namespace Omeka\Test;

use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase as ZendAbstractHttpControllerTestCase;

abstract class AbstractHttpControllerTestCase extends ZendAbstractHttpControllerTestCase
{
    public function getApplication()
    {
        // Return the application immediately if already set.
        if ($this->application) {
            return $this->application;
        }
        $config = require OMEKA_PATH . '/config/application.config.php';
        $reader = new \Zend\Config\Reader\Ini;
        $testConfig = [
            'connection' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini')
        ];
        $config = array_merge($config, $testConfig);

        \Zend\Console\Console::overrideIsConsole($this->getUseConsoleRequest());
        $this->application = \Omeka\Mvc\Application::init($config);

        $events = $this->application->getEventManager();
        $events->detach($this->application->getServiceManager()->get('SendResponseListener'));

        return $this->application;
    }
}
