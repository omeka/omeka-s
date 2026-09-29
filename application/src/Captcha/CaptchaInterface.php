<?php
namespace Omeka\Captcha;

use Laminas\Form\ElementInterface;
use Laminas\View\Renderer\PhpRenderer;

/**
 * A CAPTCHA provider used to verify whether a user is human.
 */
interface CaptchaInterface
{
    /**
     * Get the label of this CAPTCHA provider.
     */
    public function getLabel(): string;

    /**
     * Get the element specs for this provider's global settings.
     *
     * The settings form adds these to its security group and populates them
     * from the global settings, falling back to an element's "value" attribute
     * as its default. Return elements only (no fieldsets) and use names that
     * are unique across the settings form. The settings of unselected
     * providers are hidden, so their elements must be valid when left as they
     * are: don't make empty text inputs required.
     */
    public function getSettingElements(): array;

    /**
     * Are the settings needed to render and verify the CAPTCHA present?
     */
    public function isConfigured(): bool;

    /**
     * Get the name of the form field the widget submits its response in.
     */
    public function getResponseName(): string;

    /**
     * Render the CAPTCHA widget and append any scripts it needs.
     */
    public function render(PhpRenderer $view, ElementInterface $element): string;

    /**
     * Verify a submitted CAPTCHA response.
     */
    public function verify(string $response, ?string $remoteIp): bool;
}
