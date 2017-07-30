<?php
namespace Omeka\Media\Ingester;

use Omeka\Api\Request;
use Omeka\Entity\Media;
use Omeka\File\Downloader;
use Omeka\Stdlib\ErrorStore;
use Zend\Form\Element\Text;
use Zend\Form\Element\Url as UrlElement;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class Youtube implements IngesterInterface
{
    /**
     * @var Downloader
     */
    protected $downloader;

    public function __construct(Downloader $downloader)
    {
        $this->downloader = $downloader;
    }

    public function getLabel()
    {
        return 'YouTube'; // @translate
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

        $url = sprintf('http://img.youtube.com/vi/%s/0.jpg', $youtubeId);
        $tempFile = $this->downloader->download($url);
        if ($tempFile) {
            if ($tempFile->storeThumbnails()) {
                $media->setStorageId($tempFile->getStorageId());
                $media->setHasThumbnails(true);
            }
            $tempFile->delete();
        }

        $mediaData = ['id' => $youtubeId];
        $start = trim($request->getValue('start'));
        if (is_numeric($start)) {
            $mediaData['start'] = $start;
        }
        $end = trim($request->getValue('end'));
        if (is_numeric($end)) {
            $mediaData['end'] = $end;
        }
        $media->setData($mediaData);
    }

    /**
     * {@inheritDoc}
     */
    public function form(PhpRenderer $view, array $options = [])
    {
        $urlInput = new UrlElement('o:media[__index__][o:source]');
        $urlInput->setOptions([
            'label' => 'Video URL', // @translate
            'info' => 'URL for the video to embed.', // @translate
        ]);
        $urlInput->setAttributes([
            'id' => 'media-youtube-source-__index__',
            'required' => true,
        ]);
        $urlInput->setAttributes([
            'id' => 'media-youtube-source-__index__',
            'required' => true,
        ]);
        $startInput = new Text('o:media[__index__][start]');
        $startInput->setOptions([
            'label' => 'Start', // @translate
            'info' => 'Begin playing the video at the given number of seconds from the start of the video.', // @translate
        ]);
        $endInput = new Text('o:media[__index__][end]');
        $endInput->setOptions([
            'label' => 'End', // @translate
            'info' => 'End playing the video at the given number of seconds from the start of the video.', // @translate
        ]);
        return $view->formRow($urlInput)
            . $view->formRow($startInput)
            . $view->formRow($endInput);
    }
}
