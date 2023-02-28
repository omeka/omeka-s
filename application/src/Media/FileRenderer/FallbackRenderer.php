<?php
namespace Omeka\Media\FileRenderer;

class FallbackRenderer extends AbstractRenderer
{
    public function render(PhpRenderer $view, MediaRepresentation $media, array $options = [])
    {
        $link = $options['link'] ?? 'original';
        return $view->hyperlink($media->filename(), $this->getLinkUrl($media, $link));
    }
}
