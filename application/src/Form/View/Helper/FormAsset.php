<?php
namespace Omeka\Form\View\Helper;

use Omeka\Api\Representation\AssetRepresentation;
use Zend\Form\View\Helper\AbstractHelper;
use Zend\Form\ElementInterface;

class FormAsset extends AbstractHelper
{
    public function __invoke(ElementInterface $element, AssetRepresentation $asset = null)
    {
        return $this->render($element, $asset);
    }

    /**
     * Render the asset form.
     *
     * @param ElementInterface $element The asset element with type Omeka\Form\Element\Asset
     * @return string
     */
    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        return $view->partial('common/asset-form', ['element' => $element]);
    }
}
