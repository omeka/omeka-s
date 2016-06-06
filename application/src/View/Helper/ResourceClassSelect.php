<?php
namespace Omeka\View\Helper;

use Omeka\Form\Element\ResourceClassSelect as Select;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * A select menu containing all resource classes.
 */
class ResourceClassSelect extends AbstractHelper
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $formElementManager;

    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function __invoke($name, array $options = [])
    {
        if (!isset($options['empty_option'])) {
            $options['empty_option'] = 'Select Class...'; // @translate
        }
        $element = $this->formElementManager->get(Select::class)
            ->setName($name)->setOptions($options);
        return $this->getView()->formSelect($element);
    }
}
