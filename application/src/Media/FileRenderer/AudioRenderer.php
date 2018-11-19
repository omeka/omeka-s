<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Representation\MediaRepresentation;
use Zend\View\Renderer\PhpRenderer;

class AudioRenderer implements RendererInterface
{
    const DEFAULT_OPTIONS = [
        'controls' => true,
    ];

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = []
    ) {
        $options = array_merge(self::DEFAULT_OPTIONS, $options);

        $attrs[] = sprintf('src="%s"', $view->escapeHtml($media->originalUrl()));

        if (isset($options['autoplay']) && $options['autoplay']) {
            $attrs[] = 'autoplay';
        }
        if (isset($options['controls']) && $options['controls']) {
            $attrs[] = 'controls';
        }
        if (isset($options['loop']) && $options['loop']) {
            $attrs[] = 'loop';
        }
        if (isset($options['muted']) && $options['muted']) {
            $attrs[] = 'muted';
        }

        return sprintf(
            '<audio %s>%s</audio>',
            implode(' ', $attrs),
            $view->hyperlink($media->filename(), $media->originalUrl())
        );
    }
}
