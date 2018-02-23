<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering a browse filtering form.
 */
class FilterSelector extends AbstractHelper
{
    const PARTIAL_NAME = 'common/filter-selector';

    /**
     * Render a browse filtering form.
     *
     * @param array $filters An array of arrays containg filter "value" and "label"
     * @param string $partialName
     * @return string
     */
    public function __invoke(array $filters, $partialName = null)
    {
        return $this->getView()->partial(
            $partialName ?: self::PARTIAL_NAME,
            ['filters' => $filters]
        );
    }
}
