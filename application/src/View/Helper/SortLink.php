<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering a sortable link.
 */
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
        $params = $this->getView()->params();
        $sortByQuery = $params->fromQuery('sort_by');
        $sortOrderQuery = $params->fromQuery('sort_order');

        if ('asc' === $sortOrderQuery && $sortByQuery === $sortBy) {
            $sortOrder = 'desc';
            $class = 'sorted-asc';
        } elseif ('desc' === $sortOrderQuery && $sortByQuery === $sortBy) {
            $sortOrder = 'asc';
            $class = 'sorted-desc';
        } else {
            $sortOrder = 'asc';
            $class = 'sortable';
        }
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $url = $this->getView()->url(
            null, [], [
                'query' => [
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ] + $params->fromQuery(),
            ],
            true
        );

        return $this->getView()->partial(
            $partialName,
            [
                'label' => $label,
                'url' => $url,
                'class' => $class,
                'sortBy' => $sortBy,
                'sortOrder' => $sortOrder,
            ]
        );
    }
}
