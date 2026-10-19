<?php
namespace Omeka\Captcha;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = CaptchaInterface::class;

    /**
     * Get the active CAPTCHA provider.
     *
     * Returns null when no provider is selected, when the selected provider is
     * not registered, or when it is not configured.
     */
    public function getActive(): ?CaptchaInterface
    {
        $name = $this->creationContext->get('Omeka\Settings')->get('captcha');
        if (!$name || !$this->has($name)) {
            return null;
        }
        $captcha = $this->get($name);
        return $captcha->isConfigured() ? $captcha : null;
    }
}
