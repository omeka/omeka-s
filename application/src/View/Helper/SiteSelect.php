<?php
namespace Omeka\View\Helper;
use Zend\Form\Element\Select;


/**
 * A select menu containing all sites.
 */
class SiteSelect extends AbstractSelect
{

  protected $emptyOption = 'All sites';

   public function getValueOptions()
     {
         $sites = $this->getView()->api()->search('sites')->getContent();
         $options = [];
         foreach ($sites as $site) {
           $options[$site->id()] = $site->title();
         }

        return  $options;
    }


}
