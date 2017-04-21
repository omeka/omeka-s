<?php
namespace Omeka\Form;

use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\ResourceClassSelect;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\Event;
use Zend\Form\Element;
use Zend\Form\Form;

class ResourceBatchUpdateForm extends Form
{
    use EventManagerAwareTrait;

    public function init()
    {}
}
