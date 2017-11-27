<?php
namespace OmekaTest\View\Helper;

use Omeka\View\Helper\Pagination;
use Omeka\Test\TestCase;

class PaginationTest extends TestCase
{
    public function testToString()
    {
        $totalCount = 1000;
        $currentPage = 50;
        $perPage = 10;
        $pageCount = 100;
        $previousPage = 49;
        $nextPage = 51;
        $name = 'name';
        $query = ['foo' => 'bar'];

        // Request
        $request = $this->getMockBuilder('Zend\Http\PhpEnvironment\Request')
            ->setMethods(['getQuery', 'toArray'])
            ->getMock();
        $request->expects($this->any())
            ->method('getQuery')
            ->will($this->returnSelf());
        $request->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue($query));

        // Omeka\Pagination
        $paginator = $this->createMock('Omeka\Stdlib\Paginator');
        $paginator->expects($this->any())
            ->method('setTotalCount')
            ->with($this->equalTo($totalCount));
        $paginator->expects($this->any())
            ->method('setCurrentPage')
            ->with($this->equalTo($currentPage));
        $paginator->expects($this->any())
            ->method('setPerPage')
            ->with($this->equalTo($perPage));
        $paginator->expects($this->any())
            ->method('getPageCount')
            ->will($this->returnValue($pageCount));
        $paginator->expects($this->any())
            ->method('getTotalCount')
            ->will($this->returnValue($totalCount));
        $paginator->expects($this->any())
            ->method('getPerPage')
            ->will($this->returnValue($perPage));
        $paginator->expects($this->any())
            ->method('getCurrentPage')
            ->will($this->returnValue($currentPage));
        $paginator->expects($this->any())
            ->method('getPreviousPage')
            ->will($this->returnValue($previousPage));
        $paginator->expects($this->any())
            ->method('getNextPage')
            ->will($this->returnValue($nextPage));

        // View
        $view = $this->getMockBuilder('Zend\View\Renderer\PhpRenderer')
            ->setMethods(['partial', 'url', 'params'])
            ->getMock();
        $view->expects($this->any())
            ->method('url');
        $view->expects($this->once())
            ->method('partial')
            ->with(
                $this->equalTo($name),
                $this->equalTo([
                    'totalCount' => $totalCount,
                    'perPage' => $perPage,
                    'currentPage' => $currentPage,
                    'previousPage' => $previousPage,
                    'nextPage' => $nextPage,
                    'pageCount' => $pageCount,
                    'query' => $query,
                    'firstPageUrl' => null,
                    'previousPageUrl' => null,
                    'nextPageUrl' => null,
                    'lastPageUrl' => null,
                    'pagelessUrl' => null,
                    'offset' => null,
                ])
            );
        $params = $this->getMockBuilder('Omeka\View\Helper\Params')
            ->disableOriginalConstructor()
            ->getMock();
        $params->expects($this->exactly(6))
            ->method('fromQuery')
            ->will($this->returnValue($query));
        $view->expects($this->exactly(6))
            ->method('params')
            ->will($this->returnValue($params));

        $pagination = new Pagination($paginator);
        $pagination->setView($view);
        $pagination->__invoke($name, $totalCount, $currentPage, $perPage);
        $pagination->__toString();
    }
}
