<?php
namespace Omeka\Captcha;

use Laminas\Form\ElementInterface;
use Laminas\View\Renderer\PhpRenderer;

/**
 * Google reCAPTCHA v2, "I'm not a robot" checkbox.
 *
 * Uses the same settings, markup, and scripts as the deprecated
 * Omeka\Form\Element\Recaptcha, so both can appear on one page.
 */
class RecaptchaV2 extends AbstractSiteverify
{
    public function getLabel(): string
    {
        return 'reCAPTCHA v2 (checkbox)'; // @translate
    }

    public function getResponseName(): string
    {
        return 'g-recaptcha-response';
    }

    public function render(PhpRenderer $view, ElementInterface $element): string
    {
        // Defines the callback that renders the widgets once the API loads.
        $view->headScript()->appendFile($view->assetUrl('js/recaptcha.js', 'Omeka'));
        return parent::render($view, $element);
    }

    protected function getSiteKeySetting(): string
    {
        // These settings predate this provider and are read directly by
        // modules, so they keep their original, unversioned names.
        return 'recaptcha_site_key';
    }

    protected function getSecretKeySetting(): string
    {
        return 'recaptcha_secret_key';
    }

    protected function getSiteKeyLabel(): string
    {
        return 'reCAPTCHA site key'; // @translate
    }

    protected function getSecretKeyLabel(): string
    {
        return 'reCAPTCHA secret key'; // @translate
    }

    protected function getScriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js?onload=recaptchaCallback&render=explicit';
    }

    protected function getWidgetClass(): string
    {
        return 'g-recaptcha';
    }

    protected function getVerifyUrl(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }
}
