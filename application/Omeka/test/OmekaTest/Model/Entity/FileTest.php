<?php
namespace OmekaTest\Model;

use Omeka\Model\Entity\File;
use Omeka\Test\TestCase;

class FileTest extends TestCase
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
