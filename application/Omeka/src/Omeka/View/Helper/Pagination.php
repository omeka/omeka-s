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
            // Cast to a zero or positive integer
            $this->totalCount = abs((int) $totalCount);
        }
        if (null !== $page) {
            // Cast to a non-zero positive integer
            $page = abs((int) $page);
            if (1 > $page) {
                $page = 1;
            }
            $this->page = $page;
        }
        if (null !== $perPage) {
            // Cast to a non-zero positive integer
            $perPage = abs((int) $perPage);
            if (1 > $perPage) {
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

        // Pext page number
        $next = null;
        if ($this->page + 1 <= $pageCount) {
            $next = $this->page + 1;
        }

        $output = '
        <nav class="pagination" role="navigation">
            <form>
                <input type="text" name="page" id="page-input-top" value="' . $this->page . '" size="4">
                <span class="page-count">of ' . $pageCount . '</span>
            </form>';
        if ($this->page != 1) {
            $output .= '<a href="' . $this->getUrl(1) . '" class="first fa-angle-double-left button"><span class="screen-reader-text">First</span></a> ';
        }
        if ($this->page != 1) {
            $output .= '<a href="' . $this->getUrl($previous) . '" class="previous fa-angle-left button"><span class="screen-reader-text">Previous</span></a> ';
        }
        if ($this->page < $pageCount) {
            $output .= '<a href="' . $this->getUrl($next) . '" class="next fa-angle-right button"><span class="screen-reader-text">Next</span></a> ';
        }
        if ($this->page < $pageCount) {
            $output .= '<a href="' . $this->getUrl($pageCount) . '" class="last fa-angle-double-right button"><span class="screen-reader-text">Last</span></a>';
        }
        $output .= '</nav>';
        return $output;
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
