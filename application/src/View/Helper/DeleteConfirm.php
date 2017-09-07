<?php
namespace Omeka\View\Helper;

use Omeka\Form\ConfirmForm;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * View helper for rendering the delete confirm partial.
 */
class DeleteConfirm extends AbstractHelper
{
    protected $formElementManager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $formElementManager
     */
    public function __construct(ServiceLocatorInterface $formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    /**
     * Render the delete confirm partial.
     *
     * @param RepresentationInterface $resource
     * @param string $resourceLabel
     * @param bool $wrapSidebar
     * @return string
     */
    public function __invoke($resource, $resourceLabel = null, $wrapSidebar = true)
    {
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
