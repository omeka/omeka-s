<?php
namespace Mapping\Collecting;

use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Form\ElementInterface;

class FormPromptMap extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();
        $value = $element->getValue();
        $lat = $value['lat'] ?? '';
        $lng = $value['lng'] ?? '';
        $label = $value['label'] ?? '';
        return sprintf('
            <input type="hidden" class="collecting-map-lat" name="%1$s[lat]" value="%2$s">
            <input type="hidden" class="collecting-map-lng" name="%1$s[lng]" value="%3$s">
            <div class="collecting-map" style="height:300px;"></div>
            <input type="text" class="collecting-map-label" name="%1$s[label]" value="%4$s" placeholder="%5$s">',
            $element->getName(),
            $view->escapeHtml($lat),
            $view->escapeHtml($lng),
            $view->escapeHtml($label),
            $view->translate('Enter a marker label')
        );
    }
}
