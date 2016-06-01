<?php
namespace Omeka\View\Helper;

use Omeka\Form\ConfirmForm;
use Zend\View\Helper\AbstractHelper;

class DeleteConfirm extends AbstractHelper
{
    protected $formElementManager;

    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function __invoke($resource, $resourceLabel = null, $buttonLabel = null) {

        if (!isset($buttonLabel)) {
            $buttonLabel = 'Confirm Delete'; // @translate
        }

        $form = $this->formElementManager->get(ConfirmForm::class);
        $form->setAttribute('action', $resource->url('delete'));
        $form->setButtonLabel($buttonLabel);

        return $this->getView()->partial(
            'common/delete-confirm',
            [
                'wrapSidebar' => true,
                'resourceLabel' => $resourceLabel,
                'resource' => $resource,
                'form' => $form,
            ]
        );
    }
}
