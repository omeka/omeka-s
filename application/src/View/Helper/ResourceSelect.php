<?php
namespace Omeka\View\Helper;

use Omeka\Form\Element\ResourceSelect as Select;
use Zend\Form\Factory;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * A select menu containing all of some resource.
 */
class ResourceSelect extends AbstractHelper
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $formElementManager;

    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function __invoke(array $spec = [])
    {
        $spec['type'] = Select::class;
        $factory = new Factory($this->formElementManager);
        $element = $factory->createElement($spec);
        return $this->getView()->formSelect($element);
    }
}
