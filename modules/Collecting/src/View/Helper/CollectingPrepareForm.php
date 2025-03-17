<?php
namespace Collecting\View\Helper;

use Collecting\MediaType\Manager;
use Laminas\View\Helper\AbstractHelper;

class CollectingPrepareForm extends AbstractHelper
{
    /**
     * @var Manager
     */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function __invoke()
    {
        // Enable the CKEditor HTML text editors.
        $this->getView()->ckEditor();

        // Map the HTML element type to the view helper that renders it.
        $this->getView()->formElement()->addType('promptHtml', 'formPromptHtml');

        // Call each media type's prepareForm()
        $mediaTypeNames = $this->manager->getRegisteredNames();
        foreach ($mediaTypeNames as $mediaTypeName) {
            $this->manager->get($mediaTypeName)->prepareForm($this->getView());
        }
    }
}
