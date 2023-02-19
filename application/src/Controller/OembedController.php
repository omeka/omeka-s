<?php
namespace Omeka\Controller;

use Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Uri\Http as HttpUrl;
use Laminas\View\Model\JsonModel;

class OembedController extends AbstractActionController
{
    public function indexAction()
    {
        // @see https://oembed.com/
        $response = $this->getResponse();
        $format = $this->params()->fromQuery('format', 'json');
        if ('json' !== $format) {
            // Invalid format. Return 501 Not Implemented.
            $response->setStatusCode(501);
            return $response;
        }
        $url = new HttpUrl($this->params()->fromQuery('url'));
        if (!$url->isValid()) {
            // Invalid URL passed in url query parameter. Return 404 Not Found.
            $response->setStatusCode(404);
            return $response;
        }
        // This pattern matches public resource page URLs.
        $isMatch = preg_match('#^.+/s/.+/(item|media)/(\d+)$#i', $url->getPath(), $matches);
        if (!$isMatch) {
            // Invalid resource URL passed in url query parameter. Return 404 Not Found.
            $response->setStatusCode(404);
            return $response;
        }
        [$path, $resourceType, $resourceId] = $matches;
        if ('item' === $resourceType) {
            $resourceType = 'items';
        } elseif ('media' === $resourceType) {
            $resourceType = 'media';
        }
        try {
            $resource = $this->api()->read($resourceType, $resourceId)->getContent();
        } catch (Exception $e) {
            // Omeka makes no distinction between a 404 Not Found and a 401
            // Unauthorized error when finding a resource. Return 404 here
            // as a catch-all.
            $response->setStatusCode(404);
            return $response;
        }
        // Build the oEmbed response.
        $oembed = [
            'type' => 'rich',
            'version' => '1.0',
            'title' => $resource->displayTitle(),
            'html' => sprintf('<iframe width="800" height="600" src="%s"></iframe>', htmlspecialchars($url->toString())),
        ];
        if ($primaryMedia = $resource->primaryMedia()) {
            $oembed['thumbnail_url'] = $primaryMedia->thumbnailUrl('square');
            $oembed['thumbnail_width'] = 200;
            $oembed['thumbnail_height'] = 200;
        }
        $jsonModel = new JsonModel($oembed);
        $jsonModel->setOption('prettyPrint', true);
        return $jsonModel;
    }
}
