<?php
namespace OmekaTest\Model;

use Omeka\Entity\SiteBlockAttachment;
use Omeka\Entity\SitePageBlock;
use Omeka\Test\TestCase;

class SiteBlockAttachmentTest extends TestCase
{
    protected $attachment;

    public function setUp()
    {
        $this->attachment = new SiteBlockAttachment;
    }

    public function testInitialState()
    {
        $this->assertNull($this->attachment->getId());
        $this->assertNull($this->attachment->getBlock());
    }

    public function testSetBlock()
    {
        $block = new SitePageBlock;
        $this->attachment->setBlock($block);
        $this->assertSame($block, $this->attachment->getBlock());
    }
}
