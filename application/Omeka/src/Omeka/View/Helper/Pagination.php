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
     * @var int
     */
    protected $totalCount = 0;

    /**
     * @var int
     */
    protected $perPage = 25;

    /**
     * @var int
     */
    protected $page = 1;

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
     * @param int $totalCount The total count
     * @param int $page The current page
     * @param int $perPage The count per page
     * @return self
     */
    public function __invoke($totalCount = null, $page = null, $perPage = null)
    {
        if (null !== $totalCount) {
            $this->totalCount = abs((int) $totalCount);
        }
        if (null !== $page) {
            $page = abs((int) $page);
            if (1 > $page) {
                $page = 1;
            }
            $this->page = $page;
        }
        if (null !== $perPage) {
            $this->perPage = abs((int) $perPage);
        }
        return $this;
    }

    /**
     * Render the pagination.
     *
     * @return string
     */
    public function __toString()
    {
        $pageCount = ceil($this->totalCount / $this->perPage);

        // previous page number
        $previous = null;
        if ($this->page - 1 > 0) {
            $previous = $this->page - 1;
        }

        // next page number
        $next = null;
        if ($this->page + 1 <= $pageCount) {
            $next = $this->page + 1;
        }

        $output = '
        <nav class="pagination" role="navigation">
            <form>
                <input type="text" name="page-input-top" id="page-input-top" value="' . $this->page . '" size="4">
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
