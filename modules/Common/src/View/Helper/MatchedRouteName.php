<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class MatchedRouteName extends AbstractHelper
{
    /**
     * @var string
     */
    protected $route;

    public function __construct(string $route)
    {
        $this->route = $route;
    }

    /**
     * Get the route name for the current request.
     *
     * It is not available in view, neither in view status, unlike controller.
     * @see \Omeka\View\Helper\Status
     * @see \Omeka\Mvc\Controller\Plugin\Status
     * @link https://stackoverflow.com/questions/12068648/zend-framework-2-get-matched-route-in-view/36337414#36337414
     */
    public function __invoke(): string
    {
        return $this->route;
    }
}
