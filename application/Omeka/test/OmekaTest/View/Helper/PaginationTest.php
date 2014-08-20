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
        $name = 'name';
        $query = array('foo' => 'bar');

        // Request
        $request = $this->getMock(
            'Zend\Http\PhpEnvironment\Request',
            array('getQuery', 'toArray')
        );
        $request->expects($this->any())
            ->method('getQuery')
            ->will($this->returnSelf());
        $request->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue($query));

        // Omeka\Options
        $options = $this->getMock('Omeka\Service\Options');
        $options->expects($this->once())
            ->method('get')
            ->with($this->equalTo('pagination_per_page'), $this->equalTo(25))
            ->will($this->returnValue($perPage));

        // ServiceManager
        $serviceManager = $this->getServiceManager(array(
            'Request' => $request,
            'Omeka\Options' => $options,
        ));

        // View
        $view = $this->getMock(
            'Zend\View\Renderer\PhpRenderer',
            array('partial', 'url')
        );
        $view->expects($this->any())
            ->method('url');
        $view->expects($this->once())
            ->method('partial')
            ->with(
                $this->equalTo($name),
                $this->equalTo(array(
                    'totalCount'      => 1000,
                    'perPage'         => 10,
                    'currentPage'     => 50,
                    'previousPage'    => 49,
                    'nextPage'        => 51,
                    'pageCount'       => 100,
                    'query'           => array('foo' => 'bar'),
                    'firstPageUrl'    => null,
                    'previousPageUrl' => null,
                    'nextPageUrl'     => null,
                    'lastPageUrl'     => null,
                ))
            );

        $pagination = new Pagination($serviceManager);
        $pagination->setView($view);
        $pagination->__invoke($totalCount, $currentPage, $perPage, $name);
        $pagination->__toString();
    }
}
