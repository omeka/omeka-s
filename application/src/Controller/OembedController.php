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
            $response->setStatusCode(501);
            return $response;
        }
        $url = $this->params()->fromQuery('url');
        if (!$url) {
            $response->setStatusCode(404);
            return $response;
        }
        $url = new HttpUrl($url);
        $isMatch = preg_match('#^.+/s/.+/(item|media)/(\d+)$#i', $url->getPath(), $matches);
        if (!$isMatch) {
            $response->setStatusCode(404);
            return $response;
        }
        [$url, $resourceType, $resourceId] = $matches;
        if ('item' === $resourceType) {
            $resourceType = 'items';
        } elseif ('media' === $resourceType) {
            $resourceType = 'media';
        }
        try {
            $resource = $this->api()->read($resourceType, $resourceId)->getContent();
        } catch (Exception $e) {
            $response->setStatusCode(404);
            return $response;
        }
        $oembed = [
            'type' => 'rich',
            'version' => '1.0',
            'title' => $resource->displayTitle(),
            'html' => $resource->displayValues(),
        ];
        return new JsonModel($oembed);
    }
}
