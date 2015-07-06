<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Handler\Manager;
use Omeka\Media\Handler\MutableHandlerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Exception;
use Zend\View\Helper\AbstractHelper;

class Media extends AbstractHelper
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->manager = $serviceLocator->get('Omeka\MediaHandlerManager');
    }

    /**
     * Return the HTML necessary to render an add form.
     *
     * @param string $mediaType
     * @param array $options Global options for the media type
     * @return string
     */
    public function form($mediaType, array $options = array())
    {   
        $form = '<div class="media-field-wrapper">';
        $form .= '<ul class="actions"> <li><a href="#" class="o-icon-public" aria-label="Make private" title="Make private"></a>
            <input type="hidden" name="o:media[__index__][o:is_public]" value="1"></li>
            <li><a class="o-icon-delete remove-new-media-field" href="#"" title="Remove value" aria-label="Remove value"></a></li>
            </ul>';
        $form .= $this->manager->get($mediaType)
            ->form($this->getView(), $options);
        $form .= '<input type="hidden" name="o:media[__index__][o:type]" value="'
            . $this->getView()->escapeHtmlAttr($mediaType) . '">';
        $form .= '</div>';
        return $form;
    }

    /**
     * Return the HTML necessary to render an edit form.
     *
     * @param MediaRepresentation $media
     * @param array $options Global options for the media type
     * @return string
     */
    public function updateForm(MediaRepresentation $media, array $options = array())
    {
        $handler = $this->manager->get($media->type());

        if ($handler instanceof MutableHandlerInterface) {
            return $handler->updateForm($this->getView(), $media, $options);
        } else {
            return '';
        }
    }

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param MediaRepresentation $media
     * @param array $options Global options for the media type
     * @return string
     */
    public function render(MediaRepresentation $media, array $options = array())
    {
        return $this->manager->get($media->type())
            ->render($this->getView(), $media, $options);
    }
}