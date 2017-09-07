<?php
namespace Omeka\View\Helper;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Media\Ingester\Manager as IngesterManager;
use Omeka\Media\Ingester\MutableIngesterInterface;
use Omeka\Media\Renderer\Manager as RendererManager;
use Zend\Form\Element\Hidden;
use Zend\Form\Element\Text;
use Zend\Form\Factory;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering media.
 */
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
     * @var int
     */
    protected $titleId;

    /**
     * Construct the helper.
     *
     * @param IngesterManager $ingesterManager,
     * @param RendererManager $rendererManager,
     */
    public function __construct(IngesterManager $ingesterManager,
        RendererManager $rendererManager
    ) {
        $this->ingesterManager = $ingesterManager;
        $this->rendererManager = $rendererManager;
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
        if (!$this->titleId) {
            // Cache the ID of the dcterms:title property
            $this->titleId = $this->getView()->api()
                ->searchOne('properties', ['term' => 'dcterms:title'])
                ->getContent()->id();
        }

        $factory = new Factory;
        $titleValue = $factory->createElement([
            'type' => Text::class,
            'name' => 'o:media[__index__][dcterms:title][0][@value]',
            'options' => [
                'label' => 'Title', // @translate
            ],
        ]);
        $titleProperty = $factory->createElement([
            'type' => Hidden::class,
            'name' => 'o:media[__index__][dcterms:title][0][property_id]',
            'attributes' => ['value' => $this->titleId],
        ]);
        $titleType = $factory->createElement([
            'type' => Hidden::class,
            'name' => 'o:media[__index__][dcterms:title][0][type]',
            'attributes' => ['value' => 'literal'],
        ]);

        return $this->getView()->partial('common/media-field-wrapper.phtml', [
            'titleValue' => $titleValue,
            'titleProperty' => $titleProperty,
            'titleType' => $titleType,
            'ingesterName' => $ingesterName,
            'ingester' => $this->ingesterManager->get($ingesterName),
            'options' => $options,
        ]);
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

        if ($ingester instanceof MutableIngesterInterface) {
            return $ingester->updateForm($this->getView(), $media, $options);
        }
        return '';
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
        $renderedMedia = $this->rendererManager->get($media->renderer())
            ->render($this->getView(), $media, $options);
        return sprintf('<div class="media-render">%s</div>', $renderedMedia);
    }
}
