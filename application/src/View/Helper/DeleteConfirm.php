<?php
namespace Omeka\View\Helper;

use Omeka\Form\ConfirmForm;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

class DeleteConfirm extends AbstractHelper
{
    protected $formElementManager;

    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    public function __invoke($resource, $resourceLabel = null, $wrapSidebar = true) {
        $form = $this->formElementManager->get(ConfirmForm::class);
        $form->setAttribute('action', $resource->url('delete'));

        return $this->getView()->partial(
            'common/delete-confirm',
            [
                'wrapSidebar' => $wrapSidebar,
                'resource' => $resource,
                'resourceLabel' => $resourceLabel,
                'form' => $form,
            ]
        );
    }
}
