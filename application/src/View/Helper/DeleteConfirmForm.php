<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;
use Omeka\Form\ConfirmForm;

class DeleteConfirmForm extends AbstractHelper
{
    protected $serviceLocator;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function __invoke($record, $buttonText = null) {
        $translate = $this->getView()->plugin('translate');
        if (!isset($buttonText)) {
            $buttonText = $translate('Confirm delete');
        }
        $confirmForm = new ConfirmForm(
            $this->serviceLocator, null, [
                'button_value' => $buttonText
            ]
        );
        $confirmForm->setAttribute('action', $record->url('delete'));
        return $confirmForm;
    }
}
