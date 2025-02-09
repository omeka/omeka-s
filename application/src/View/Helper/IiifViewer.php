<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class IiifViewer extends AbstractHelper
{
    /**
     * Render the Omeka S IIIF viewer in an iframe.
     *
     * The $query array should include a "url" parameter that points to a IIIF
     * manifest or collection. It may also include limited viewer-specific
     * configurations. See `Omeka\Controller\IiifViewerController` for more
     * information.
     *
     * @param array $query
     * @param array $options
     * @return string
     */
    public function __invoke(array $query, array $options = [])
    {
        $view = $this->getView();
        $width = $options['width'] ?? '100%';
        $height = $options['height'] ?? '700px';
        $src = $view->url('iiif-viewer', [], ['force_canonical' => true, 'query' => $query]);
        return sprintf('<iframe style="width: %s; height: %s;" src="%s"></iframe>', $width, $height, $view->escapeHtml($src));
    }
}
