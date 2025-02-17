<?php declare(strict_types=1);

namespace Common\Form\Element;

use Omeka\Api\Representation\AbstractRepresentation;
use Omeka\Api\Representation\ResourceReference;
use Omeka\Api\Representation\SitePageRepresentation;

/**
 * Different from core SitePageSelect: all the pages of all sites are returned.
 *
 * @see \Omeka\Form\Element\SitePageSelect
 */
class SitesPageSelect extends AbstractGroupBySiteSelect
{
    public function getResourceName(): string
    {
        return 'site_pages';
    }

    public function getValueLabel(SitePageRepresentation $resource): string
    {
        return $resource->title();
    }

    public function setValue($value)
    {
        $isMultiple = !empty($this->attributes['multiple']);
        if ($isMultiple) {
            foreach ($value as &$val) {
                if ($val instanceof AbstractRepresentation
                    || $val instanceof ResourceReference
                ) {
                    $val = $val->id();
                } elseif (is_array($val)) {
                    $val = $val['o:id'] ?? null;
                }
            }
            unset($val);
        } else {
            if ($value instanceof AbstractRepresentation
                || $value instanceof ResourceReference
            ) {
                $value = $value->id();
            } elseif (is_array($value)) {
                $value = $value['o:id'] ?? null;
            }
        }

        return parent::setValue($value);
    }
}
