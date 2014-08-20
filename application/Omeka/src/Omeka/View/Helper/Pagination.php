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
     * @var \Omeka\Service\Options
     */
    protected $options;

    /**
     * The total record count
     *
     * @var int
     */
    protected $totalCount = 0;

    /**
     * The current page number
     *
     * @var int
     */
    protected $currentPage = 1;

    /**
     * The number of records per page
     *
     * @var int
     */
    protected $perPage = 25;

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
        $this->options = $serviceLocator->get('Omeka\Options');
        $this->perPage = $this->options->get(
            'pagination_per_page', $this->perPage
        );
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
            $totalCount = (int) $totalCount;
            if ($totalCount < 0) {
                $totalCount = 0;
            }
            $this->totalCount = $totalCount;
        }
        if (null !== $currentPage) {
            $currentPage = (int) $currentPage;
            if ($currentPage < 1) {
                $currentPage = 1;
            }
            $this->currentPage = $currentPage;
        }
        if (null !== $perPage) {
            $perPage = (int) $perPage;
            if ($perPage < 1) {
                $perPage = 1;
            }
            $this->perPage = $perPage;
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
        // Page count
        $pageCount = ceil($this->totalCount / $this->perPage);

        // Current page number cannot be more than page count
        if ($this->currentPage > $pageCount) {
            $this->currentPage = $pageCount;
        }

        // Previous page number
        $previousPage = null;
        if ($this->currentPage - 1 > 0) {
            $previousPage = $this->currentPage - 1;
        }

        // Next page number
        $nextPage = null;
        if ($this->currentPage + 1 <= $pageCount) {
            $nextPage = $this->currentPage + 1;
        }

        return $this->getView()->partial(
            $this->name,
            array(
                'totalCount'      => $this->totalCount,
                'perPage'         => $this->perPage,
                'currentPage'     => $this->currentPage,
                'previousPage'    => $previousPage,
                'nextPage'        => $nextPage,
                'pageCount'       => $pageCount,
                'query'           => $this->request->getQuery()->toArray(),
                'firstPageUrl'    => $this->getUrl(1),
                'previousPageUrl' => $this->getUrl($previousPage),
                'nextPageUrl'     => $this->getUrl($nextPage),
                'lastPageUrl'     => $this->getUrl($pageCount),
            )
        );
    }

    /**
     * Get a pagination URL.
     *
     * @param int $page The page number
     * @return string
     */
    protected function getUrl($page)
    {
        return $this->getView()->url(
            null,
            array(),
            array(
                'query' => array_merge(
                    $this->request->getQuery()->toArray(),
                    array('page' => (int) $page)
                )
            ),
            true
        );
    }
}
