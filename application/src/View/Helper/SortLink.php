<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class SortLink extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/sort-link';

    /**
     * Render a sortable link.
     *
     * @param string $label
     * @param string $sortBy
     * @param string|null $partialName Name of view script, or a view model
     * @return string
     */
    public function __invoke($label, $sortBy, $partialName = null)
    {
        if (!isset($_GET['sort_by'])) {
            $_GET['sort_by'] = null;
        }
        if (!isset($_GET['sort_order'])) {
            $_GET['sort_order'] = null;
        }
        if ('asc' == $_GET['sort_order'] && $_GET['sort_by'] == $sortBy) {
            $sortOrder = 'desc';
            $class = 'sorted-asc';
        } elseif ('desc' == $_GET['sort_order'] && $_GET['sort_by'] == $sortBy) {
            $sortOrder = 'asc';
            $class = 'sorted-desc';
        } else {
            $sortOrder = 'asc';
            $class = 'sortable';
        }
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $url = $this->getView()->url(
            null, array(), array(
                'query' => array(
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ) + $_GET
            ),
            true
        );

        return $this->getView()->partial(
            $partialName,
            array(
                'label'     => $label,
                'url'       => $url,
                'class'     => $class,
                'sortBy'    => $sortBy,
                'sortOrder' => $sortOrder,
            )
        );
    }
}
