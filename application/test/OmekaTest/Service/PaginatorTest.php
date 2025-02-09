<?php
namespace OmekaTest\Service;

use Omeka\Stdlib\Paginator;
use Omeka\Test\TestCase;

class PaginatorTest extends TestCase
{
    public function testSetCurrentPage()
    {
        $paginator = new Paginator;

        $return = $paginator->setCurrentPage(10);
        $this->assertSame($paginator, $return);
        $this->assertEquals(10, $paginator->getCurrentPage());

        $paginator->setCurrentPage(0);
        $this->assertEquals(1, $paginator->getCurrentPage());
    }

    public function testSetPerPage()
    {
        $paginator = new Paginator;

        $return = $paginator->setPerPage(10);
        $this->assertSame($paginator, $return);
        $this->assertEquals(10, $paginator->getPerPage());

        $paginator->setPerPage(0);
        $this->assertEquals(1, $paginator->getPerPage());
    }

    public function testSetTotalCount()
    {
        $paginator = new Paginator;

        $return = $paginator->setTotalCount(10);
        $this->assertSame($paginator, $return);
        $this->assertEquals(10, $paginator->getTotalCount());

        $paginator->setTotalCount(-1);
        $this->assertEquals(0, $paginator->getTotalCount());
    }

    public function testGetOffset()
    {
        $paginator = new Paginator;
        $paginator->setPerPage(10)
            ->setCurrentPage(5);
        $this->assertEquals(40, $paginator->getOffset());
    }

    public function testGetPageCount()
    {
        $paginator = new Paginator;
        $paginator->setPerPage(10)
            ->setTotalCount(50);
        $this->assertEquals(5, $paginator->getPageCount());
    }

    public function testGetPreviousPage()
    {
        $paginator = new Paginator;

        $paginator->setCurrentPage(5);
        $this->assertEquals(4, $paginator->getPreviousPage());

        $paginator->setCurrentPage(1);
        $this->assertNull($paginator->getPreviousPage());
    }

    public function testGetNextPage()
    {
        $paginator = new Paginator;

        $paginator->setCurrentPage(1)
            ->setTotalCount(50)
            ->setPerPage(10);
        $this->assertEquals(2, $paginator->getNextPage());

        $paginator->setCurrentPage(5)
            ->setTotalCount(50)
            ->setPerPage(10);
        $this->assertNull($paginator->getNextPage());
    }
}
