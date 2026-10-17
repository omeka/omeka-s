<?php
namespace Omeka\Captcha;

use Laminas\Form\ElementInterface;
use Laminas\Http\Client;
use Laminas\Http\Exception\ExceptionInterface as HttpException;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Settings\Settings;

/**
 * A CAPTCHA provider that verifies responses with a remote "siteverify" API.
 *
 * The widget renders into a div with a data-sitekey attribute, and the
 * response is verified by POSTing the secret key, the response, and the remote
 * IP address to the provider, which returns JSON with a "success" key.
 */
abstract class AbstractSiteverify implements CaptchaInterface
{
    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var Client
     */
    protected $client;

    public function __construct(Settings $settings, Client $client)
    {
        $this->settings = $settings;
        $this->client = $client;
    }

    /**
     * Get the name of the site key setting.
     */
    abstract protected function getSiteKeySetting(): string;

    /**
     * Get the name of the secret key setting.
     */
    abstract protected function getSecretKeySetting(): string;

    /**
     * Get the label of the site key setting.
     */
    abstract protected function getSiteKeyLabel(): string;

    /**
     * Get the label of the secret key setting.
     */
    abstract protected function getSecretKeyLabel(): string;

    /**
     * Get the URL of the widget script.
     */
    abstract protected function getScriptUrl(): string;

    /**
     * Get the class of the div the widget renders into.
     */
    abstract protected function getWidgetClass(): string;

    /**
     * Get the URL of the siteverify API.
     */
    abstract protected function getVerifyUrl(): string;

    public function getSettingElements(): array
    {
        return [
            [
                'type' => 'text',
                'name' => $this->getSiteKeySetting(),
                'options' => [
                    'label' => $this->getSiteKeyLabel(),
                ],
                'attributes' => [
                    'id' => $this->getSiteKeySetting(),
                ],
            ],
            [
                'type' => 'text',
                'name' => $this->getSecretKeySetting(),
                'options' => [
                    'label' => $this->getSecretKeyLabel(),
                ],
                'attributes' => [
                    'id' => $this->getSecretKeySetting(),
                ],
            ],
        ];
    }

    public function isConfigured(): bool
    {
        return '' !== $this->getSiteKey() && '' !== $this->getSecretKey();
    }

    public function render(PhpRenderer $view, ElementInterface $element): string
    {
        $view->headScript()->appendFile(
            $this->getScriptUrl(),
            'text/javascript',
            ['async' => true, 'defer' => true]
        );
        return sprintf(
            '<div class="%s" data-sitekey="%s"></div>',
            $view->escapeHtmlAttr($this->getWidgetClass()),
            $view->escapeHtmlAttr($this->getSiteKey())
        );
    }

    public function verify(string $response, ?string $remoteIp): bool
    {
        $apiResponse = $this->requestVerification($response, $remoteIp);
        return $apiResponse && true === ($apiResponse['success'] ?? false);
    }

    /**
     * Request verification from the siteverify API.
     *
     * Returns the decoded API response, or null if the request failed or the
     * response could not be decoded.
     */
    protected function requestVerification(string $response, ?string $remoteIp): ?array
    {
        try {
            $httpResponse = $this->client
                ->setUri($this->getVerifyUrl())
                ->setMethod('POST')
                ->setParameterPost($this->getVerifyParams($response, $remoteIp))
                ->send();
        } catch (HttpException $e) {
            // Includes connection failures and empty or malformed responses.
            return null;
        }
        $apiResponse = json_decode($httpResponse->getBody(), true);
        return is_array($apiResponse) ? $apiResponse : null;
    }

    /**
     * Get the parameters to POST to the siteverify API.
     */
    protected function getVerifyParams(string $response, ?string $remoteIp): array
    {
        return [
            'secret' => $this->getSecretKey(),
            'response' => $response,
            'remoteip' => $remoteIp,
        ];
    }

    protected function getSiteKey(): string
    {
        return trim((string) $this->settings->get($this->getSiteKeySetting()));
    }

    protected function getSecretKey(): string
    {
        return trim((string) $this->settings->get($this->getSecretKeySetting()));
    }
}
