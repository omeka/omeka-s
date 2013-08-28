<?php

namespace OmekaTest\Controller;

use PHPUnit_Framework_TestCase;
use OmekaTest\Bootstrap;

class OmekaTest extends PHPUnit_Framework_TestCase
{
    public function setUp() 
    {
        parent::setUp();
    }

    public function testApplicationConfigIsArray()
    {
        $config = Bootstrap::getApplicationConfig();
        $this->assertTrue(is_array($config));
    }
}