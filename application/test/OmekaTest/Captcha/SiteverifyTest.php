<?php
namespace OmekaTest\Captcha;

use Omeka\Captcha\Hcaptcha;
use Omeka\Captcha\RecaptchaV3;
use Omeka\Captcha\Turnstile;
use Omeka\Test\TestCase;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Test as TestAdapter;
use Laminas\View\Renderer\PhpRenderer;

class SiteverifyTest extends TestCase
{
    protected $adapter;

    protected $client;

    public function setUp(): void
    {
        $this->adapter = new TestAdapter;
        $this->client = new Client(null, ['adapter' => $this->adapter]);
    }

    public function testGetSettingElements()
    {
        $names = array_column($this->getTurnstile()->getSettingElements(), 'name');
        $this->assertSame(['turnstile_site_key', 'turnstile_secret_key'], $names);
    }

    public function testIsConfigured()
    {
        $this->assertTrue($this->getTurnstile()->isConfigured());
        $this->assertFalse($this->getTurnstile(['turnstile_secret_key' => ''])->isConfigured());
        $this->assertFalse($this->getTurnstile(['turnstile_site_key' => '  '])->isConfigured());
    }

    public function testRender()
    {
        $view = new PhpRenderer;
        $markup = $this->getTurnstile()->render($view, new \Laminas\Form\Element('test'));
        $this->assertSame('<div class="cf-turnstile" data-sitekey="site-key"></div>', $markup);
        $srcs = [];
        foreach ($view->headScript()->getContainer() as $item) {
            $srcs[] = $item->attributes['src'];
        }
        $this->assertSame(['https://challenges.cloudflare.com/turnstile/v0/api.js'], $srcs);
    }

    public function testVerifySuccess()
    {
        $this->setResponse('{"success":true}');
        $this->assertTrue($this->getTurnstile()->verify('token', '192.0.2.1'));

        $this->assertSame(
            'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            $this->client->getUri()->toString()
        );
        $this->assertSame([
            'secret' => 'secret-key',
            'response' => 'token',
            'remoteip' => '192.0.2.1',
        ], $this->getPostedParams());
    }

    public function testVerifyFailure()
    {
        $this->setResponse('{"success":false,"error-codes":["invalid-input-response"]}');
        $this->assertFalse($this->getTurnstile()->verify('token', '192.0.2.1'));
    }

    public function testVerifyInvalidResponse()
    {
        $this->setResponse('Not JSON');
        $this->assertFalse($this->getTurnstile()->verify('token', '192.0.2.1'));
    }

    public function testVerifyEmptyResponse()
    {
        // The client throws Laminas\Http\Exception\RuntimeException.
        $this->adapter->setResponse('');
        $this->assertFalse($this->getTurnstile()->verify('token', '192.0.2.1'));
    }

    public function testVerifyMalformedResponse()
    {
        // Not a valid HTTP response, so parsing it throws.
        $this->adapter->setResponse('garbage');
        $this->assertFalse($this->getTurnstile()->verify('token', '192.0.2.1'));
    }

    public function testVerifyRequestFailure()
    {
        $this->adapter->setNextRequestWillFail(true);
        $this->assertFalse($this->getTurnstile()->verify('token', '192.0.2.1'));
    }

    public function testHcaptchaSendsSiteKey()
    {
        $this->setResponse('{"success":true}');
        $hcaptcha = new Hcaptcha($this->getSettings([
            'hcaptcha_site_key' => 'site-key',
            'hcaptcha_secret_key' => 'secret-key',
        ]), $this->client);
        $this->assertTrue($hcaptcha->verify('token', '192.0.2.1'));
        $this->assertSame('site-key', $this->getPostedParams()['sitekey']);
    }

    public function testRecaptchaV3SettingElements()
    {
        $elements = $this->getRecaptchaV3()->getSettingElements();
        $this->assertSame(
            ['recaptcha_v3_site_key', 'recaptcha_v3_secret_key', 'recaptcha_v3_score_threshold'],
            array_column($elements, 'name')
        );
        $this->assertSame('0.5', $elements[2]['attributes']['value']);
    }

    /**
     * @dataProvider recaptchaV3VerifyProvider
     */
    public function testRecaptchaV3Verify($body, $threshold, $expected)
    {
        $this->setResponse($body);
        $recaptcha = $this->getRecaptchaV3(['recaptcha_v3_score_threshold' => $threshold]);
        $this->assertSame($expected, $recaptcha->verify('token', '192.0.2.1'));
    }

    public function recaptchaV3VerifyProvider()
    {
        return [
            'score above threshold' => ['{"success":true,"action":"submit","score":0.9}', '0.5', true],
            'score at threshold' => ['{"success":true,"action":"submit","score":0.5}', '0.5', true],
            'score below threshold' => ['{"success":true,"action":"submit","score":0.3}', '0.5', false],
            'raised threshold' => ['{"success":true,"action":"submit","score":0.6}', '0.7', false],
            'default threshold' => ['{"success":true,"action":"submit","score":0.4}', null, false],
            'wrong action' => ['{"success":true,"action":"login","score":0.9}', '0.5', false],
            'no score' => ['{"success":true,"action":"submit"}', '0.5', false],
            'not successful' => ['{"success":false,"action":"submit","score":0.9}', '0.5', false],
        ];
    }

    protected function getRecaptchaV3(array $values = [])
    {
        $values = array_merge([
            'recaptcha_v3_site_key' => 'site-key',
            'recaptcha_v3_secret_key' => 'secret-key',
        ], $values);
        return new RecaptchaV3($this->getSettings($values), $this->client);
    }

    protected function getTurnstile(array $values = [])
    {
        $values = array_merge([
            'turnstile_site_key' => 'site-key',
            'turnstile_secret_key' => 'secret-key',
        ], $values);
        return new Turnstile($this->getSettings($values), $this->client);
    }

    protected function getSettings(array $values)
    {
        $settings = $this->createMock('Omeka\Settings\Settings');
        $settings->method('get')->willReturnCallback(fn ($id) => $values[$id] ?? null);
        return $settings;
    }

    protected function setResponse($body)
    {
        $this->adapter->setResponse("HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . $body);
    }

    protected function getPostedParams()
    {
        $rawRequest = $this->client->getLastRawRequest();
        parse_str(substr($rawRequest, strpos($rawRequest, "\r\n\r\n") + 4), $params);
        return $params;
    }
}
