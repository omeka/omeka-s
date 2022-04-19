<?php

namespace Omeka\Test;

use Laminas\Test\PHPUnit\Controller\AbstractHttpControllerTestCase as ZendAbstractHttpControllerTestCase;
use Omeka\Mvc\Application;

abstract class AbstractHttpControllerTestCase extends ZendAbstractHttpControllerTestCase
{
    public function setUp(): void
    {
        $config = require OMEKA_PATH . '/application/config/application.config.php';
        $reader = new \Laminas\Config\Reader\Ini;
        $testConfig = [
            'connection' => $reader->fromFile(OMEKA_PATH . '/application/test/config/database.ini'),
        ];
        $config = array_merge($config, $testConfig);
        $this->setApplicationConfig($config);

        parent::setUp();
    }

    public function getApplication()
    {
        if ($this->application) {
            return $this->application;
        }

        $appConfig = $this->applicationConfig;
        $this->application = Application::init($appConfig);

        $events = $this->application->getEventManager();
        $this->application->getServiceManager()->get('SendResponseListener')->detach($events);

        return $this->application;
    }
}
