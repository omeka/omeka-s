<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\File;

class FileTest extends \PHPUnit_Framework_TestCase
{
    protected $file;

    public function setUp()
    {
        $this->file = new File;
    }

    public function testInitialState()
    {
        $this->assertNull($this->file->getId());
    }

    public function testSetState()
    {
    }
}
