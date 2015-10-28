<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\Form\Element\Url as UrlElement;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class Youtube extends AbstractIngester
{
    public function getLabel()
    {
        $translator = $this->getServiceLocator()->get('MvcTranslator');
        return $translator->translate('YouTube');
    }

    public function getRenderer()
    {
        return 'youtube';
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
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

        $fileManager = $this->getServiceLocator()->get('Omeka\File\Manager');
        $file = $this->getServiceLocator()->get('Omeka\File');

        $url = sprintf('http://img.youtube.com/vi/%s/0.jpg', $youtubeId);
        $this->downloadFile($url, $file->getTempPath());
        $hasThumbnails = $fileManager->storeThumbnails($file);

        $media->setData([
            'id' => $youtubeId,
            'start' => $request->getValue('start'),
            'end' => $request->getValue('end'),
        ]);
        if ($hasThumbnails) {
            $media->setFilename($file->getStorageName());
            $media->setHasThumbnails(true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => $view->translate('Video URL'),
            'info' => $view->translate('URL for the video to embed.'),
        ]);
        $urlInput->setAttributes([
            'id' => 'media-youtube-source-__index__',
            'required' => true
        ]);
        $urlInput->setAttributes([
            'id' => 'media-youtube-source-__index__',
            'required' => true
        ]);
        $startInput = new Text('o:media[__index__][start]');
        $startInput->setOptions([
            'label' => $view->translate('Start'),
            'info' => $view->translate('Begin playing the video at the given number of seconds from the start of the video.'),
        ]);
        $endInput = new Text('o:media[__index__][end]');
        $endInput->setOptions([
            'label' => $view->translate('End'),
            'info' => $view->translate('End playing the video at the given number of seconds from the start of the video.'),
        ]);
        return $view->formField($urlInput)
            . $view->formField($startInput)
            . $view->formField($endInput);
    }
}
