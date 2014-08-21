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
     * @var \Omeka\Service\Pagination
     */
    protected $pagination;

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
        $this->pagination = $serviceLocator->get('Omeka\Pagination');
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
            $this->getPagination()->setTotalCount($totalCount);
        }
        if (null !== $currentPage) {
            $this->getPagination()->setCurrentPage($currentPage);
        }
        if (null !== $perPage) {
            $this->getPagination()->setPerPage($perPage);
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
        $pagination = $this->getPagination();

        // Page count
        $pageCount = $pagination->getPageCount();

        // Current page number cannot be more than page count
        if ($pagination->getCurrentPage() > $pageCount) {
            $pagination->setCurrentPage($pageCount);
        }

        return $this->getView()->partial(
            $this->name,
            array(
                'totalCount'      => $pagination->getTotalCount(),
                'perPage'         => $pagination->getPerPage(),
                'currentPage'     => $pagination->getCurrentPage(),
                'previousPage'    => $pagination->getPreviousPage(),
                'nextPage'        => $pagination->getNextPage(),
                'pageCount'       => $pageCount,
                'query'           => $this->request->getQuery()->toArray(),
                'firstPageUrl'    => $this->getUrl(1),
                'previousPageUrl' => $this->getUrl($pagination->getPreviousPage()),
                'nextPageUrl'     => $this->getUrl($pagination->getNextPage()),
                'lastPageUrl'     => $this->getUrl($pageCount),
            )
        );
    }

    protected function getPagination()
    {
        return $this->pagination;
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
