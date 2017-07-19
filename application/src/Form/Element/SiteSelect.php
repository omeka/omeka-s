<?php
namespace Omeka\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Omeka\Api\Representation\UserRepresentation;
use Zend\Form\Element\Select;

class SiteSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function getValueOptions()
    {
        $valueOptions = [];
        $response = $this->getApiManager()->search('sites');
        // Group alphabetically by owner email.
        $siteOwners = [];
        foreach ($response->getContent() as $site) {
            $owner = $site->owner();
            $index = $owner ? $owner->email() : null;
            $siteOwners[$index]['owner'] = $owner;
            $siteOwners[$index]['sites'][] = $site;
        }
        ksort($siteOwners);
        foreach ($siteOwners as $siteOwner) {
            $options = [];
            foreach ($siteOwner['sites'] as $site) {
                $options[$site->id()] = $site->title();
                if (!$options) {
                    continue;
                }
            }
            $owner = $siteOwner['owner'];
            if ($owner instanceof UserRepresentation) {
                $label = sprintf('%s (%s)', $owner->name(), $owner->email());
            } else {
                $label = '[No owner]';
            }
            $valueOptions[] = ['label' => $label, 'options' => $options];
        }
        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        return $valueOptions;
    }

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }
}
