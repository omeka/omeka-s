<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class SortSelector extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/sort-selector';

    /**
     * Render form for sorting.
     *
     * @param array $sortBy
     * @param string|null $partialName Name of view script, or a view model
     * @return array
     */
    public function __invoke($sortBy, $partialName = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;

        $translate = $this->getView()->plugin('translate');

        $params = $this->getView()->params();
        $sortByQuery = $params->fromQuery('sort_by');
        $sortOrderQuery = $params->fromQuery('sort_order');

        return $this->getView()->partial(
            $partialName,
            [
                'sortBy'            => $sortBy,
                'sortByQuery'       => $sortByQuery,
                'sortOrderQuery'    => $sortOrderQuery
            ]
        );
    }
}
