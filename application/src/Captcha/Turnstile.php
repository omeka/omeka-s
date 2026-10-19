<?php
namespace Omeka\Captcha;

class Turnstile extends AbstractSiteverify
{
    public function getLabel(): string
    {
        return 'Cloudflare Turnstile'; // @translate
    }

    public function getResponseName(): string
    {
        return 'cf-turnstile-response';
    }

    protected function getSiteKeySetting(): string
    {
        return 'turnstile_site_key';
    }

    protected function getSecretKeySetting(): string
    {
        return 'turnstile_secret_key';
    }

    protected function getSiteKeyLabel(): string
    {
        return 'Turnstile site key'; // @translate
    }

    protected function getSecretKeyLabel(): string
    {
        return 'Turnstile secret key'; // @translate
    }

    protected function getScriptUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    protected function getWidgetClass(): string
    {
        return 'cf-turnstile';
    }

    protected function getVerifyUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }
}
