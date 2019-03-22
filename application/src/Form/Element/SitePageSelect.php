<?php
namespace Omeka\Form\Element;

use Zend\Form\Element\Select;

class SitePageSelect extends Select
{
    protected $site;

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function getValueOptions()
    {
        $valueOptions = [];
        foreach ($this->getSite()->pages() as $sitePage) {
            $valueOptions[$sitePage->id()] = sprintf('%s (%s)', $sitePage->title(), $sitePage->slug());
        }
        return $valueOptions;
    }
}
