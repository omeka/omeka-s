<?php
namespace OmekaTest\Service;

use Omeka\Service\Pagination;
use Omeka\Test\TestCase;

class PaginationTest extends TestCase
{
    public function testSetServiceLocator()
    {
        $perPage = 10;

        $options = $this->getMock('Omeka\Service\Options');
        $options->expects($this->once())
            ->method('get')
            ->with($this->equalTo('pagination_per_page'), $this->equalTo(25))
            ->will($this->returnValue($perPage));
        $serviceManager = $this->getServiceManager(array(
            'Omeka\Options' => $options,
        ));

        $pagination = new Pagination;
        $pagination->setServiceLocator($serviceManager);
        $this->assertEquals($perPage, $pagination->getPerPage());
        $this->assertSame($serviceManager, $pagination->getServiceLocator());
    }

    public function testSetCurrentPage()
    {
        $pagination = new Pagination;

        $return = $pagination->setCurrentPage(10);
        $this->assertSame($pagination, $return);
        $this->assertEquals(10, $pagination->getCurrentPage());

        $pagination->setCurrentPage(0);
        $this->assertEquals(1, $pagination->getCurrentPage());
    }

    public function testSetPerPage()
    {
        $pagination = new Pagination;

        $return = $pagination->setPerPage(10);
        $this->assertSame($pagination, $return);
        $this->assertEquals(10, $pagination->getPerPage());

        $pagination->setPerPage(0);
        $this->assertEquals(1, $pagination->getPerPage());
    }

    public function testSetTotalCount()
    {
        $pagination = new Pagination;

        $return = $pagination->setTotalCount(10);
        $this->assertSame($pagination, $return);
        $this->assertEquals(10, $pagination->getTotalCount());

        $pagination->setTotalCount(-1);
        $this->assertEquals(0, $pagination->getTotalCount());
    }

    public function testGetOffset()
    {
        $pagination = new Pagination;
        $pagination->setPerPage(10)
            ->setCurrentPage(5);
        $this->assertEquals(40, $pagination->getOffset());
    }

    public function testGetPageCount()
    {
        $pagination = new Pagination;
        $pagination->setPerPage(10)
            ->setTotalCount(50);
        $this->assertEquals(5, $pagination->getPageCount());
    }

    public function testGetPreviousPage()
    {
        $pagination = new Pagination;

        $pagination->setCurrentPage(5);
        $this->assertEquals(4, $pagination->getPreviousPage());

        $pagination->setCurrentPage(1);
        $this->assertNull($pagination->getPreviousPage());
    }

    public function testGetNextPage()
    {
        $pagination = new Pagination;

        $pagination->setCurrentPage(1)
            ->setTotalCount(50)
            ->setPerPage(10);
        $this->assertEquals(2, $pagination->getNextPage());

        $pagination->setCurrentPage(5)
            ->setTotalCount(50)
            ->setPerPage(10);
        $this->assertNull($pagination->getNextPage());
    }
}
