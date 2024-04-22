<?php
namespace Omeka\Stdlib;

use Laminas\Dom\Query;
use Laminas\Http\Client as HttpClient;
use Laminas\I18n\Translator\TranslatorInterface;
use Laminas\Uri\Http as HttpUri;
use Laminas\View\Renderer\PhpRenderer;

class Oembed
{
    protected $allowList;

    protected $client;

    protected $translator;

    public function __construct(array $allowList, HttpClient $client, TranslatorInterface $translator)
    {
        $this->allowList = $allowList;
        $this->client = $client;
        $this->translator = $translator;
    }

    /**
     * Get an oEmbed response.
     *
     * @param string $url
     * @param ErrorStore $errorStore
     * @param string $errorKey
     * @return array|false
     */
    public function getOembed(string $url, ErrorStore $errorStore, string $errorKey = 'oembed-url')
    {
        // Check that the URL is allowed.
        $allowed = false;
        foreach ($this->allowList as $pattern) {
            if (1 === preg_match($pattern, $url)) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            $errorStore->addError($errorKey, sprintf($this->translator->translate('oEmbed: URL is not allowed %s'), $url));
            return false;
        }

        // Check for oEmbed support.
        // @see https://oembed.com/#section4
        $response = $this->getResponse($url, $errorStore, $errorKey);
        if (!$response) {
            return false;
        }
        $dom = new Query($response->getBody());
        $oembedLinks = $dom->queryXpath('//link[@rel="alternate" or @rel="alternative"][@type="application/json+oembed" or @type="text/json+oembed"]');
        if (!$oembedLinks->count()) {
            $errorStore->addError($errorKey, sprintf($this->translator->translate('oEmbed: links cannot be found at %s'), $url));
            return false;
        }

        // Get the oEmbed response.
        $oembedLinkUrl = $oembedLinks->current()->getAttribute('href');
        $response = $this->getResponse($oembedLinkUrl, $errorStore, $errorKey);
        if (!$response) {
            return false;
        }
        $oembed = json_decode($response->getBody(), true);
        if (!$oembed) {
            $errorStore->addError($errorKey, sprintf($this->translator->translate('oEmbed: response cannot be decoded to JSON %s'), $oembedLinkUrl));
            return false;
        }
        return $oembed;
    }

    /**
     * Get oEmbed HTML markup.
     *
     * @param PhpRenderer $view
     * @param array $oembed
     * @return string|false
     */
    public function renderOembed(PhpRenderer $view, array $oembed)
    {
        if (isset($oembed['html'])) {
            return $oembed['html'];
        }
        $type = $oembed['type'] ?? null;
        if ('photo' === $type) {
            $url = $oembed['url'] ?? null;
            return sprintf(
                '<img src="%s" width="%s" height="%s" alt="%s">',
                $view->escapeHtml($url),
                $view->escapeHtml($oembed['width'] ?? ''),
                $view->escapeHtml($oembed['height'] ?? ''),
                $view->escapeHtml($data['title'] ?? $url)
            );
        }
        return false;
    }

    /**
     * Make a HTTP request.
     *
     * @param string $url
     * @param ErrorStore $errorStore
     * @param string $errorKey
     * @return \Laminas\Http\Response|false
     */
    protected function getResponse(string $url, ErrorStore $errorStore, string $errorKey)
    {
        $uri = new HttpUri($url);
        if (!($uri->isValid() && $uri->isAbsolute())) {
            $errorStore->addError($errorKey, sprintf(
                $this->translator->translate('oEmbed: URL is invalid: %s'),
                $url
            ));
            return false;
        }
        $this->client->setUri($uri);
        $response = $this->client->send();
        if (!$response->isSuccess()) {
            $errorStore->addError($errorKey, sprintf(
                $this->translator->translate('oEmbed: URL is unreadable at %s: %s (%s)'),
                $url,
                $response->getReasonPhrase(),
                $response->getStatusCode()
            ));
            return false;
        }
        return $response;
    }
}
