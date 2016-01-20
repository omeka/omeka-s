<?php
namespace Omeka\DataType;

use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\View\Renderer\PhpRenderer;

class Literal extends AbstractDataType
{
    public function getLabel($dataType)
    {
        return 'Literal';
    }

    public function getTemplate(PhpRenderer $view, $dataType)
    {
        return $view->partial('common/data-type/literal');
    }

    public function isValid(array $valueObject)
    {
        if (isset($valueObject['@value'])
            && is_string($valueObject['@value'])
            && '' !== trim($valueObject['@value'])
        ) {
            return true;
        }
        return false;
    }

    public function hydrate(array $valueObject, Value $value)
    {
        $value->setType($valueObject['type']);
        $value->setValue($valueObject['@value']);
        if (isset($valueObject['@language'])) {
            $value->setLang($valueObject['@language']);
        } else {
            $value->setLang(null); // set default
        }
        $value->setUriLabel(null); // set default
        $value->setValueResource(null); // set default
    }

    public function getHtml(PhpRenderer $view, ValueRepresentation $value)
    {
        return nl2br($view->escapeHtml($value->value()));
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        $jsonLd = ['@value' => $value->value()];
        if ($value->lang()) {
            $jsonLd['@language'] = $value->lang();
        }
        return $jsonLd;
    }
}
