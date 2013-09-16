<?php

namespace OmekaTest;

use OmekaTest\Bootstrap;

class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationConfigIsArray()
    {
        $config = Bootstrap::getApplicationConfig();
        $this->assertTrue(is_array($config));
    }
}