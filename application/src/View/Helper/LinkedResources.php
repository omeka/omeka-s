<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class LinkedResources extends AbstractHelper
{
    public function __construct()
    {
    }

    public function __invoke(AbstractResourceEntityRepresentation $resource)
    {
        $view = $this->getView();
        $view->headScript()->appendFile($view->assetUrl('js/linked-resources.js', 'Omeka'));

        if ($view->status()->isAdminRequest()) {
            $url = $view->url('admin/id', [
                'controller' => 'index',
                'action' => 'linked-resources',
                'id' => $resource->id(),
            ], [], true);
        } elseif ($view->status()->isSiteRequest()) {
            $url = $view->url('site/resource-id', [
                'controller' => 'index',
                'action' => 'linked-resources',
                'id' => $resource->id(),
            ], [], true);
        } else {
            return '';
        }
        return sprintf('<div id="linked-resources-container" data-url="%s"></div>', $url);
    }
}
