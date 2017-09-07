<?php
namespace Omeka\View\Helper;

use Zend\I18n\Translator\TranslatorInterface;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting a BCP 47-compliant value for the lang attribute.
 */
class Lang extends AbstractHelper
{
    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * Construct the helper.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get a lang value.
     *
     * Accepts a string or gets the locale from the translator.
     *
     * @param null|string $locale
     * @return string
     */
    public function __invoke($lang = null)
    {
        if (null === $lang) {
            $lang = $this->translator->getLocale();
        }
        // BCP 47 specifies that subtags are separated by hyphens.
        return str_replace('_', '-', $lang);
    }
}
