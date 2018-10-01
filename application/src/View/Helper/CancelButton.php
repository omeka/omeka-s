<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering a cancel button.
 */
class CancelButton extends AbstractHelper
{
    public function __invoke()
    {
        $view = $this->getView();
        return $view->hyperlink($view->translate('Cancel'), '#', ['class' => 'cancel button']);
    }
}
