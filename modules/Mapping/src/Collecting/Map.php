<?php
namespace Mapping\Collecting;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Collecting\MediaType\MediaTypeInterface;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Map implements MediaTypeInterface
{
    public function getLabel()
    {
        return 'Map'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('node_modules/leaflet/dist/leaflet.css', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('node_modules/leaflet-geosearch/dist/geosearch.css', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('node_modules/leaflet.fullscreen/Control.FullScreen.css', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('css/mapping.css', 'Mapping'));

        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet/dist/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet-geosearch/dist/bundle.min.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet.fullscreen/Control.FullScreen.js', 'Mapping'));

        $view->headScript()->appendFile($view->assetUrl('js/mapping-collecting-form.js', 'Mapping'));

        $view->formElement()->addType('promptMap', 'formPromptMap');
    }

    public function form(Form $form, CollectingPromptRepresentation $prompt, $name)
    {
        $element = new PromptMap($name);
        $element->setLabel($prompt->text())
            ->setIsRequired($prompt->required());
        $form->add($element);
    }

    public function itemData(array $itemData, $postedPrompt,
        CollectingPromptRepresentation $prompt
    ) {
        $lat = null;
        $lng = null;
        // Set the dcterms:title value as the default label. One caveat: the
        // title prompt must come before the map prompt or $itemData will not
        // include the title property.
        $label = @$itemData['dcterms:title'][0]['@value'];
        if (isset($postedPrompt['lat']) && is_numeric($postedPrompt['lat'])) {
            $lat = trim($postedPrompt['lat']);
        }
        if (isset($postedPrompt['lng']) && is_numeric($postedPrompt['lng'])) {
            $lng = trim($postedPrompt['lng']);
        }
        if (isset($postedPrompt['label']) && '' !== trim($postedPrompt['label'])) {
            $label = trim($postedPrompt['label']);
        }
        if ($lat && $lng) {
            // Add marker data only when latitude and longitude are valid.
            $itemData['o-module-mapping:feature'][] = [
                'o-module-mapping:geography-type' => 'point',
                'o-module-mapping:geography-coordinates' => [$lng, $lat],
                'o:label' => $label,
            ];
        }
        return $itemData;
    }
}
