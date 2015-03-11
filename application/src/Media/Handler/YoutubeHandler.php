<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\HandlerInterface;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class YoutubeHandler implements HandlerInterface
{
    use ServiceLocatorAwareTrait;

    const WIDTH = 420;
    const HEIGHT = 315;
    const ALLOWFULLSCREEN = true;

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No YouTube URL specified');
            return;
        }

        $uri = new HttpUri($data['o:source']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified');
            return;
        }

        if ('www.youtube.com' !== $uri->getHost()) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified, not a YouTube URL');
            return;
        }

        if ('/watch' !== $uri->getPath()) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified, missing "/watch" path');
            return;
        }

        $query = $uri->getQueryAsArray();
        if (!isset($query['v'])) {
            $errorStore->addError('o:source', 'Invalid YouTube URL specified, missing "v" parameter');
            return;
        }

        // Compose the YouTube embed URL and set that to o:source.
        $embedUri = new HttpUri;
        $embedUri->setScheme('https')
            ->setHost('www.youtube.com')
            ->setPath('/embed/' . $query['v']);

        $data['o:source'] = $embedUri;
        $request->setContent($data);
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $media->setSource($data['o:source']);
    }

    public function form(PhpRenderer $view, array $options = array())
    {}

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        if (!isset($options['width'])) {
            $options['width'] = self::WIDTH;
        }
        if (!isset($options['height'])) {
            $options['height'] = self::HEIGHT;
        }
        if (!isset($options['allowfullscreen'])) {
            $options['allowfullscreen'] = self::ALLOWFULLSCREEN;
        }

        $embed = '<iframe'
               . ' width="' . $view->escapeHtmlAttr($options['width']) . '"'
               . ' height="' . $view->escapeHtmlAttr($options['height']) . '"'
               . ' src="' . $view->escapeHtmlAttr($media->source()) . '"'
               . ' frameborder="0"';
        if ($options['allowfullscreen']) {
            $embed .= ' allowfullscreen';
        }
        $embed .= '></iframe>';

        return $embed;
    }
}
