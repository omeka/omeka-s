<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\View\Renderer\PhpRenderer;

class Fallback implements IngesterInterface
{
    /**
     * @var string The name of the unknown ingester
     */
    protected $name;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return sprintf('%s [%s]', 'Unknown', $this->name); // @translate
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer()
    {
        return 'fallback';
    }

    /**
     * {@inheritDoc}
     */
    public function ingest(Media $media, Request $request,
        ErrorStore $errorStore
    ) {}

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        return '';
    }
}
