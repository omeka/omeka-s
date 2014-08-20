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
    protected $page = 1;

    /**
     * The number of records per page
     *
     * @var int
     */
    protected $perPage = 25;

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
     * @param int|null $page The current page number
     * @param int|null $perPage The number of records per page
     * @return self
     */
    public function __invoke($totalCount = null, $page = null, $perPage = null)
    {
        if (null !== $totalCount) {
            $totalCount = (int) $totalCount;
            if ($totalCount < 0) {
                $totalCount = 0;
            }
            $this->totalCount = $totalCount;
        }
        if (null !== $page) {
            $page = (int) $page;
            if ($page < 1) {
                $page = 1;
            }
            $this->page = $page;
        }
        if (null !== $perPage) {
            $perPage = (int) $perPage;
            if ($perPage < 1) {
                $perPage = 1;
            }
            $this->perPage = $perPage;
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

        // Page cannot be more than page count
        if ($this->page > $pageCount) {
            $this->page = $pageCount;
        }

        // Previous page number
        $previous = null;
        if ($this->page - 1 > 0) {
            $previous = $this->page - 1;
        }

        // Next page number
        $next = null;
        if ($this->page + 1 <= $pageCount) {
            $next = $this->page + 1;
        }

        return $this->getView()->partial(
            'common/pagination',
            array(
                'pageCount'       => $pageCount,
                'currentPage'     => $this->page,
                'previousPage'    => $previous,
                'nextPage'        => $next,
                'firstPageUrl'    => $this->getUrl(1),
                'previousPageUrl' => $this->getUrl($previous),
                'nextPageUrl'     => $this->getUrl($next),
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
        $url = $this->getView()->url(
            'admin/default',
            array(),
            array(
                'query' => array_merge(
                    $this->request->getQuery()->toArray(),
                    array('page' => (int) $page)
                )
            ),
            true
        );
        return $this->getView()->escapeHtmlAttr($url);
    }
}
