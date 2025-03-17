<?php
namespace Collecting\MediaType;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

interface MediaTypeInterface
{
    /**
     * Get a human-readable label for this media type.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Prepare the view to enable this media type in the collecting form.
     *
     * Typically used to append JavaScript to the head.
     *
     * @param PhpRenderer $view
     */
    public function prepareForm(PhpRenderer $view);

    /**
     * Add this media type to the collecting form.
     *
     * @param Form $form The collecting form object
     * @param CollectingPromptRepresentation $prompt
     * @param string $name The name used to identify this media
     */
    public function form(Form $form, CollectingPromptRepresentation $prompt, $name);

    /**
     * Set the item data needed to create the collecting media.
     *
     * @param array $itemData
     * @param mixed $postedPrompt The prompt data submitted with the form
     * @param CollectingPromptRepresentation $prompt
     * @return array The filtered item data
     */
    public function itemData(array $itemData, $postedPrompt,
        CollectingPromptRepresentation $prompt);
}
