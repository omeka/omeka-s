<?php
namespace Omeka\ColumnType;

use Laminas\Form\Element as LaminasElement;
use Laminas\Form\FormElementManager;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class Owner implements ColumnTypeInterface
{
    protected FormElementManager $formElements;

    public function __construct(FormElementManager $formElements)
    {
        $this->formElements = $formElements;
    }

    public function getLabel() : string
    {
        return 'Owner'; // @translate
    }

    public function getResourceTypes() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getMaxColumns() : ?int
    {
        return 1;
    }

    public function dataIsValid(array $data) : bool
    {
        return true;
    }

     public function prepareDataForm(PhpRenderer $view) : void
    {
    }

    public function renderDataForm(PhpRenderer $view, array $data) : string
    {
        return '';
    }

    public function getSortBy(array $data) : ?string
    {
        return 'owner';
    }

    public function renderHeader(PhpRenderer $view, array $data) : string
    {
        return $this->getlabel();
    }

    public function renderContent(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $data) : ?string
    {
        $owner = $resource->owner();
        return $owner
            ? $view->hyperlink($owner->name(), $view->url('admin/id', [
                'controller' => 'user',
                'action' => 'show',
                'id' => $owner->id()]
            )) : null;
    }
}
