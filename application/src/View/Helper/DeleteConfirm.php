<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class DeleteConfirm extends AbstractHelper
{
    public function __invoke($record, $recordLabel = null) {
        return $this->getView()->partial(
            'common/delete-confirm',
            [
                'recordLabel'   => $recordLabel,
                'record'   => $record
            ]
        );
    }
}
