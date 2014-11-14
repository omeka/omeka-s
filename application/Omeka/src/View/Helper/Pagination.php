<?php
namespace Omeka\View\Helper;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class Pagination extends AbstractHelper
{
    /**
     * @var \Zend\Http\PhpEnvironment\Request
     */
    protected $request;

    /**
     * @var \Omeka\Service\Paginator
     */
    protected $paginator;

    /**
     * Name of view script, or a view model
     *
     * @var string|\Zend\View\Model\ModelInterface
     */
    protected $name = 'common/pagination';

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->request = $serviceLocator->get('Request');
        $this->paginator = $serviceLocator->get('Omeka\Paginator');
    }

    /**
     * Configure the pagination.
     *
     * @param int|null $totalCount The total record count
     * @param int|null $currentPage The current page number
     * @param int|null $perPage The number of records per page
     * @param string|null $name Name of view script, or a view model
     * @return self
     */
    public function __invoke($totalCount = null, $currentPage = null,
        $perPage = null, $name = null
    ) {
        if (null !== $totalCount) {
            $this->getPaginator()->setTotalCount($totalCount);
        }
        if (null !== $currentPage) {
            $this->getPaginator()->setCurrentPage($currentPage);
        }
        if (null !== $perPage) {
            $this->getPaginator()->setPerPage($perPage);
        }
        if (null !== $name) {
            $this->name = $name;
        }
        return $this;
    }

    /**
     * Render the pagination markup.
     *
     * @return string
     */
    public function __toString()
    {
        $paginator = $this->getPaginator();

        // Page count
        $pageCount = $paginator->getPageCount();

        // Current page number cannot be more than page count
        if ($paginator->getCurrentPage() > $pageCount) {
            $paginator->setCurrentPage($pageCount);
        }

        return $this->getView()->partial(
            $this->name,
            array(
                'totalCount'      => $paginator->getTotalCount(),
                'perPage'         => $paginator->getPerPage(),
                'currentPage'     => $paginator->getCurrentPage(),
                'previousPage'    => $paginator->getPreviousPage(),
                'nextPage'        => $paginator->getNextPage(),
                'pageCount'       => $pageCount,
                'query'           => $this->request->getQuery()->toArray(),
                'firstPageUrl'    => $this->getUrl(1),
                'previousPageUrl' => $this->getUrl($paginator->getPreviousPage()),
                'nextPageUrl'     => $this->getUrl($paginator->getNextPage()),
                'lastPageUrl'     => $this->getUrl($pageCount),
            )
        );
    }

    protected function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * Get a pagination URL.
     *
     * @param int $page The page number
     * @return string
     */
    protected function getUrl($page)
    {
        $query = $this->request->getQuery()->toArray();
        $query['page'] = (int) $page;
        return $this->getView()->url(null, array(), array('query' => $query), true);
    }
}
