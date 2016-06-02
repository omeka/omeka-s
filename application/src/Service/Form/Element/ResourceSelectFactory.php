<?php
namespace Omeka\Service\Form\Element;

use Omeka\Form\Element\ResourceSelect;
use Zend\Form\ElementFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ResourceSelectFactory implements FactoryInterface
{
    protected $options = [];

    public function createService(ServiceLocatorInterface $elements)
    {
        $elementFactory = new ElementFactory;
        $form = $elementFactory($elements, ResourceSelect::class, $this->options);
        $form->setApiManager($elements->getServiceLocator()->get('Omeka\ApiManager'));
        return $form;
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
