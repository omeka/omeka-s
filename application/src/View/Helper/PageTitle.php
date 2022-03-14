<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering a title heading for a page.
 */
class PageTitle extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/page-title';

    /**
     * Render a title heading for a page.
     *
     * The passed title is added to the title element to the head as well as
     * returned inside an h1 for printing on the page.
     *
     * @param string $title
     * @param int $level "h" level of the heading tag to print. A level of zero
     *  or a negative value will omit the heading tag.
     * @return string
     */
    public function __invoke($title, $level = 1, $subheadLabel = null, $actionLabel = null, $partialName = null)
    {
        $partialName = $partialName ?: self::PARTIAL_NAME;
        return $this->getView()->partial(
            $partialName,
            [
                'title' => $title,
                'level' => $level,
                'subheadLabel' => $subheadLabel,
                'actionLabel' => $actionLabel,
            ]
        );
    }
}
