<?php
namespace Omeka\Media\Handler;

use Omeka\Api\Representation\Entity\MediaRepresentation;
use Omeka\Api\Request;
use Omeka\Media\Handler\HandlerInterface;
use Omeka\Model\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Zend\Dom\Query;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\Uri\Http as HttpUri;
use Zend\View\Renderer\PhpRenderer;

class OEmbedHandler implements HandlerInterface
{
    use ServiceLocatorAwareTrait;

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['o:source'])) {
            $errorStore->addError('o:source', 'No OEmbed URL specified');
            return;
        }

        $uri = new HttpUri($data['o:source']);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError('o:source', 'Invalid OEmbed URL specified');
            return;
        }
    }

    public function ingest(Media $media, Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        $source = $data['o:source'];

        $client = $this->getServiceLocator()->get('Omeka\HttpClient');
        $client->setUri($source);
        $response = $client->send();

        if (!$response->isOk()) {
            $errorStore->addError('o:source', sprintf(
                "Error reading OEmbed URI: %s (%s)",
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return;
        }

        $document = $response->getBody();
        $dom = new Query($document);
        $oEmbedLinks = $dom->queryXpath('//link[@rel="alternate" and @type="application/json+oembed"]');

        if (!count($oEmbedLinks)) {
            $errorStore->addError('o:source', 'No OEmbed links were found at the given URI');
            return;
        }

        $oEmbedLink = $oEmbedLinks[0];
        $client->setUri($oEmbedLink->getAttribute('href'));
        $response = $client->send();

        $mediaData = json_decode($response->getBody());
        $media->setData($mediaData);

        $media->setSource($data['o:source']);
    }

    public function form(PhpRenderer $view, array $options = array())
    {}

    public function render(PhpRenderer $view, MediaRepresentation $media,
        array $options = array()
    ) {
        $data = $media->mediaData();

        if ($data['type'] == 'photo') {
            $url = $data['url'];
            $width = $data['width'];
            $height = $data['height'];
            if (!empty($data['title'])) {
                $title = $data['title'];
            } else {
                $title = $url;
            }
            return sprintf(
                '<img src="%s" width="%s" height="%s" alt="%s">',
                $view->escapeHtmlAttr($url),
                $view->escapeHtmlAttr($width),
                $view->escapeHtmlAttr($height),
                $view->escapeHtmlAttr($title)
            );
        } else if (!empty($data['html'])) {
            return $data['html'];
        } else {
            $source = $media->source();
            if (!$empty($data['title'])) {
                $title = $data['title'];
            } else {
                $title = $source;
            }
            return $view->hyperlink($title, $source);
        }
    }
}
