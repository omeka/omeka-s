<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Representation\Entity\MediaRepresentation;

interface RendererInterface
{
    /**
     * Return the HTML necessary to render an add/edit form.
     *
     * @param MediaRepresentation|null $media
     * @param array $options
     * @return string
     */
    public function form(MediaRepresentation $media = null, array $options = array());

    /**
     * Return the HTML necessary to render the provided media.
     *
     * @param MediaRepresentation $media
     * @param array $options
     * @return string
     */
    public function render(MediaRepresentation $media, array $options = array());
}
