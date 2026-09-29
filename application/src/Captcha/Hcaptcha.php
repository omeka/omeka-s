<?php
namespace Omeka\Captcha;

class Hcaptcha extends AbstractSiteverify
{
    public function getLabel(): string
    {
        return 'hCaptcha'; // @translate
    }

    public function getResponseName(): string
    {
        return 'h-captcha-response';
    }

    protected function getSiteKeySetting(): string
    {
        return 'hcaptcha_site_key';
    }

    protected function getSecretKeySetting(): string
    {
        return 'hcaptcha_secret_key';
    }

    protected function getSiteKeyLabel(): string
    {
        return 'hCaptcha site key'; // @translate
    }

    protected function getSecretKeyLabel(): string
    {
        return 'hCaptcha secret key'; // @translate
    }

    protected function getScriptUrl(): string
    {
        // Don't define window.grecaptcha, which would conflict with reCAPTCHA
        // rendered on the same page.
        return 'https://js.hcaptcha.com/1/api.js?recaptchacompat=off';
    }

    protected function getWidgetClass(): string
    {
        return 'h-captcha';
    }

    protected function getVerifyUrl(): string
    {
        return 'https://api.hcaptcha.com/siteverify';
    }

    protected function getVerifyParams(string $response, ?string $remoteIp): array
    {
        // Prevents tokens issued for another site key from being accepted.
        $params = parent::getVerifyParams($response, $remoteIp);
        $params['sitekey'] = $this->getSiteKey();
        return $params;
    }
}
