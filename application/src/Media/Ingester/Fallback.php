<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\View\Renderer\PhpRenderer;

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
    public function __construct($name, TranslatorInterface $translator)
    {
        $this->name = $name;
        $this->translator = $translator;
    }

    public function getLabel()
    {
        return sprintf('%s [%s]', $this->translator->translate('Unknown'), $this->name);
    }

    public function getRenderer()
    {
        return 'fallback';
    }

    public function ingest(Media $media, Request $request,
        ErrorStore $errorStore
    ) {
    }

    public function form(PhpRenderer $view, array $options = [])
    {
        return '';
    }
}
