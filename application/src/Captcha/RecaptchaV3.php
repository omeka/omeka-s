<?php
namespace Omeka\Captcha;

use Laminas\Form\ElementInterface;
use Laminas\View\Renderer\PhpRenderer;

/**
 * Google reCAPTCHA v3, score-based.
 *
 * There is no widget. A script gets a token when the form is submitted, and
 * verification rejects responses that score below the configured threshold.
 * v3 keys are distinct from v2 keys, so this provider has its own settings.
 */
class RecaptchaV3 extends AbstractSiteverify
{
    /**
     * The action name sent with each token and checked on verification.
     */
    const ACTION = 'submit';

    /**
     * The default score threshold, as recommended by Google.
     */
    const DEFAULT_SCORE_THRESHOLD = '0.5';

    public function getLabel(): string
    {
        return 'reCAPTCHA v3 (score-based)'; // @translate
    }

    public function getResponseName(): string
    {
        // Not g-recaptcha-response, which reCAPTCHA v2 widgets use, since
        // this input is ours rather than Google's.
        return 'recaptcha-v3-response';
    }

    public function getSettingElements(): array
    {
        $thresholds = [];
        foreach (range(1, 9) as $tenths) {
            $threshold = sprintf('0.%d', $tenths);
            $thresholds[$threshold] = $threshold;
        }
        $elements = parent::getSettingElements();
        $elements[] = [
            'type' => 'select',
            'name' => 'recaptcha_v3_score_threshold',
            'options' => [
                'label' => 'reCAPTCHA v3 score threshold', // @translate
                'info' => 'reCAPTCHA v3 scores each submission from 0.0 (likely a bot) to 1.0 (likely a human). Submissions that score below this threshold are rejected.', // @translate
                'value_options' => $thresholds,
            ],
            'attributes' => [
                'id' => 'recaptcha_v3_score_threshold',
                'value' => self::DEFAULT_SCORE_THRESHOLD,
            ],
        ];
        return $elements;
    }

    public function render(PhpRenderer $view, ElementInterface $element): string
    {
        // Not async, so the API is available before anyone can submit.
        $view->headScript()->appendFile($this->getScriptUrl());
        // Gets the token when the form is submitted.
        $view->headScript()->appendFile($view->assetUrl('js/recaptcha-v3.js', 'Omeka'));
        return sprintf(
            '<input type="hidden" name="%s" class="recaptcha-v3" data-sitekey="%s" data-action="%s">',
            $view->escapeHtmlAttr($this->getResponseName()),
            $view->escapeHtmlAttr($this->getSiteKey()),
            $view->escapeHtmlAttr(self::ACTION)
        );
    }

    public function verify(string $response, ?string $remoteIp): bool
    {
        // A successful response only means the token is valid. The action
        // must match and the score must meet the threshold.
        $apiResponse = $this->requestVerification($response, $remoteIp);
        return $apiResponse
            && true === ($apiResponse['success'] ?? false)
            && self::ACTION === ($apiResponse['action'] ?? null)
            && is_numeric($apiResponse['score'] ?? null)
            && (float) $apiResponse['score'] >= $this->getScoreThreshold();
    }

    protected function getScoreThreshold(): float
    {
        $threshold = $this->settings->get('recaptcha_v3_score_threshold');
        return (float) (is_numeric($threshold) ? $threshold : self::DEFAULT_SCORE_THRESHOLD);
    }

    protected function getSiteKeySetting(): string
    {
        return 'recaptcha_v3_site_key';
    }

    protected function getSecretKeySetting(): string
    {
        return 'recaptcha_v3_secret_key';
    }

    protected function getSiteKeyLabel(): string
    {
        return 'reCAPTCHA v3 site key'; // @translate
    }

    protected function getSecretKeyLabel(): string
    {
        return 'reCAPTCHA v3 secret key'; // @translate
    }

    protected function getScriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js?render=' . rawurlencode($this->getSiteKey());
    }

    protected function getWidgetClass(): string
    {
        // Not used, since render() outputs a hidden input instead of a widget.
        return 'recaptcha-v3';
    }

    protected function getVerifyUrl(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }
}
