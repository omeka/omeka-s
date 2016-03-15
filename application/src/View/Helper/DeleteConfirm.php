<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class DeleteConfirm extends AbstractHelper
{
    public function __invoke($resource, $resourceLabel = null) {
        return $this->getView()->partial(
            'common/delete-confirm',
            [
                'resourceLabel'   => $resourceLabel,
                'resource'   => $resource
            ]
        );
    }
}
