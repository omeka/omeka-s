<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class YoutubeHandler extends AbstractHandler
{
    const WIDTH = 420;
    const HEIGHT = 315;
    const ALLOWFULLSCREEN = true;

    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('YouTube');
    }

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

        switch ($uri->getHost()) {
            case 'www.youtube.com':
                if ('/watch' !== $uri->getPath()) {
                    $errorStore->addError('o:source', 'Invalid YouTube URL specified, missing "/watch" path');
                    return;
                }

                $query = $uri->getQueryAsArray();
                if (!isset($query['v'])) {
                    $errorStore->addError('o:source', 'Invalid YouTube URL specified, missing "v" parameter');
                    return;
                }
                $youtubeId = $query['v'];
                break;
            case 'youtu.be':
                $youtubeId = substr($uri->getPath(), 1);
                break;
            default:
                $errorStore->addError('o:source', 'Invalid YouTube URL specified, not a YouTube URL');
                return;
        }

        $request->setMetadata('youtubeId', $youtubeId);
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $id = $request->getMetadata('youtubeId');

        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        $file = $this->getServiceLocator()->get('Omeka\File');

        $url = sprintf('http://img.youtube.com/vi/%s/0.jpg', $id);
        $this->downloadFile($url, $file->getTempPath());
        $hasThumbnails = $fileManager->storeThumbnails($file);

        $media->setData(array('id' => $id));
        if ($hasThumbnails) {
            $media->setFilename($file->getStorageName());
            $media->setHasThumbnails(true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = array())
    {
        $urlInput = new Text('o:media[__index__][o:source]');
        $urlInput->setOptions(array(
            'label' => $view->translate('Video URL'),
            'info' => $view->translate('URL for the video to embed.'),
        ));
        $urlInput->setAttributes(array(
            'id' => 'media-youtube-source-__index__',
            'required' => true
        ));
        return $view->formField($urlInput);
    }

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

        // Compose the YouTube embed URL and build the markup.
        $data = $media->mediaData();
        $url = sprintf('https://www.youtube.com/embed/%s', $data['id']);
        $embed = sprintf(
            '<iframe width="%s" height="%s" src="%s" frameborder="0"%s></iframe>',
            $view->escapeHtmlAttr($options['width']),
            $view->escapeHtmlAttr($options['height']),
            $view->escapeHtmlAttr($url),
            $options['allowfullscreen'] ? ' allowfullscreen' : ''
        );
        return $embed;
    }
}
