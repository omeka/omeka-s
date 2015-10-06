<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Ingester\Manager as IngesterManager;
use Omeka\Media\Renderer\Manager as RendererManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class Media extends AbstractHelper
{
    /**
     * @var IngesterManager
     */
    protected $ingesterManager;

    /**
     * @var RendererManager
     */
    protected $rendererManager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->ingesterManager = $serviceLocator->get('Omeka\MediaIngesterManager');
        $this->rendererManager = $serviceLocator->get('Omeka\MediaRendererManager');
    }

    /**
     * Return the HTML necessary to render an add form.
     *
     * @param string $ingesterName
     * @param array $options Global options for the media form
     * @return string
     */
    public function form($ingesterName, array $options = [])
    {   
        $ingester = $this->ingesterManager->get($ingesterName);
        $form = '<div class="media-field-wrapper">';
        $form .= '<div class="media-header">';
        $form .= '<ul class="actions"> <li><a href="#" class="o-icon-public" aria-label="Make private" title="Make private"></a>
            <input type="hidden" name="o:media[__index__][o:is_public]" value="1"></li>
            <li><a class="o-icon-delete remove-new-media-field" href="#"" title="Remove value" aria-label="Remove value"></a></li>
            </ul>';
        $form .= '<h4>' . $ingester->getLabel() . '</h4>';
        $form .= '</div>';
        $form .= $ingester->form($this->getView(), $options);
        $form .= '<input type="hidden" name="o:media[__index__][o:ingester]" value="'
            . $this->getView()->escapeHtml($ingesterName) . '">';
        $form .= '</div>';
        return $form;
    }

    /**
     * Return the HTML necessary to render an edit form.
     *
     * @param MediaRepresentation $media
     * @param array $options Global options for the media update form
     * @return string
     */
    public function updateForm(MediaRepresentation $media, array $options = [])
    {
        $ingester = $this->ingesterManager->get($media->ingester());

        if ($ingester instanceof MutableHandlerInterface) {
            return $ingester->updateForm($this->getView(), $media, $options);
        } else {
            return '';
        }
    }

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param MediaRepresentation $media
     * @param array $options Global options for the media render
     * @return string
     */
    public function render(MediaRepresentation $media, array $options = [])
    {
        return $this->rendererManager->get($media->renderer())
            ->render($this->getView(), $media, $options);
    }
}
