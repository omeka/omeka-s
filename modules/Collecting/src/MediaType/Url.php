<?php
namespace Collecting\MediaType;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Collecting\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Url implements MediaTypeInterface
{
    public function getLabel()
    {
        return 'URL'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function form(Form $form, CollectingPromptRepresentation $prompt, $name)
    {
        $element = new Element\PromptUrl($name);
        $element->setLabel($prompt->text())
            ->setIsRequired($prompt->required());
        $form->add($element);
    }

    public function itemData(array $itemData, $postedPrompt,
        CollectingPromptRepresentation $prompt
    ) {
        $ingestUrl = trim($postedPrompt);
        if ($prompt->required()
            || (!$prompt->required() && '' !== $ingestUrl)
        ) {
            $itemData['o:media'][$prompt->id()] = [
                'o:ingester' => 'url',
                'ingest_url' => $ingestUrl,
            ];
        }
        return $itemData;
    }
}
