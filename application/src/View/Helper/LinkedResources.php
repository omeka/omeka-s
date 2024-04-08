<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class LinkedResources extends AbstractHelper
{
    public function __construct()
    {
    }

    public function __invoke(AbstractResourceEntityRepresentation $resource, int $siteId = null)
    {
        $view = $this->getView();
        $view->headScript()->appendFile($view->assetUrl('js/linked-resources.js', 'Omeka'));
        return sprintf(
            '<div id="linked-resources-container" data-url="%s" data-site-id="%s"></div>',
            $view->url('linked-resources', ['resource-id' => $resource->id()]),
            $view->escapeHtml($siteId)
        );
    }
}
