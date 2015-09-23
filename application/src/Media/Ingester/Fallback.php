<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Renderer\PhpRenderer;

class Fallback implements IngesterInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {}

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
    public function form(PhpRenderer $view, array $options = array())
    {}
}
