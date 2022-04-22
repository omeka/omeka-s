<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\View\Helper\AbstractHelper;
use Laminas\View\Renderer\PhpRenderer;

class BrowseColumns extends AbstractHelper
{
    /**
     * Is this column type valid for this resource?
     */
    public function isValid(string $type, string $resourceName) : bool
    {
        return in_array($resourceName, $this->getColumnType($type)->getCompatibleResourceNames());
    }

    /**
     * Get the header for this column.
     */
    public function getHeader(string $type, ?string $header, array $options) : ?string
    {
        return $header ?? $this->getColumnType($type)->getHeader($this->getView(), $options);
    }

    /**
     * Get the data for this column.
     */
    public function getData(string $type, AbstractResourceEntityRepresentation $resource, ?string $default, array $options) : ?string
    {
        return $this->getColumnType($type)->getData($this->getView(), $resource, $options) ?? $default;
    }

    /**
     * Get the column type service.
     */
    protected function getColumnType(string $type)
    {
        switch ($type) {
            case 'id':
                return new BrowseColumns_Id;
                break;
            case 'is_public':
                return new BrowseColumns_IsPublic;
                break;
            case 'resource_class':
                return new BrowseColumns_ResourceClass;
                break;
            case 'resource_template':
                return new BrowseColumns_ResourceTemplate;
                break;
            case 'owner':
                return new BrowseColumns_Owner;
                break;
            case 'created':
                return new BrowseColumns_Created;
                break;
            case 'modified':
                return new BrowseColumns_Modified;
                break;
            case 'property_value':
                return new BrowseColumns_PropertyValue;
                break;
            default:
                return new BrowseColumns_Unknown;
        }
    }
}

interface BrowseColumnsInterface
{
    /**
     * Get a human-readable label for this browse column.
     */
    public function getLabel() : string;

    /**
     * Get the names of resources that are compatible with this browse column.
     */
    public function getCompatibleResourceNames() : array;

    /**
     * Get the header for this browse column.
     */
    public function getHeader(PhpRenderer $view, array $options) : string;

    /**
     * Get the data for this browse column.
     */
    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string;
}

class BrowseColumns_Id implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'ID'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $resource->id();
    }
}

class BrowseColumns_IsPublic implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Is public'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $resource->isPublic()
            ? $view->translate('Yes')
            : $view->translate('No');
    }
}

class BrowseColumns_ResourceClass implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Resource class'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $resource->displayResourceClassLabel();
    }
}

class BrowseColumns_ResourceTemplate implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Resource template'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $resource->displayResourceTemplateLabel();
    }
}

class BrowseColumns_Owner implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Owner'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
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

class BrowseColumns_Created implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Created'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $view->i18n()->dateFormat($resource->created());
    }
}

class BrowseColumns_Modified implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Modified'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return $view->translate($this->getLabel());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $view->i18n()->dateFormat($resource->modified());
    }
}

class BrowseColumns_PropertyValue implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Property value'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return ['items', 'item_sets', 'media'];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        $property = $view->api()->searchOne('properties', ['term' => $options['property_term']])->getContent();
        return $view->translate($property->label());
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return $resource->value($options['property_term']);
    }
}

class BrowseColumns_Unknown implements BrowseColumnsInterface
{
    public function getLabel() : string
    {
        return 'Unknown'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [];
    }

    public function getHeader(PhpRenderer $view, array $options) : string
    {
        return '';
    }

    public function getData(PhpRenderer $view, AbstractResourceEntityRepresentation $resource, array $options) : ?string
    {
        return '';
    }
}
