<?php
namespace Collecting\MediaType;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Collecting\Form\Element;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Html implements MediaTypeInterface
{
    public function getLabel()
    {
        return 'HTML'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function form(Form $form, CollectingPromptRepresentation $prompt, $name)
    {
        $element = new Element\PromptTextarea($name);
        $element->setLabel($prompt->text())
            ->setAttribute('class', 'collecting-html')
            ->setIsRequired($prompt->required());
        $form->add($element);
    }

    public function itemData(array $itemData, $postedPrompt,
        CollectingPromptRepresentation $prompt
    ) {
        $html = trim($postedPrompt);
        if ($prompt->required()
            || (!$prompt->required() && '' !== $html)
        ) {
            $itemData['o:media'][$prompt->id()] = [
                'o:ingester' => 'html',
                'html' => $html,
            ];
        }
        return $itemData;
    }
}
