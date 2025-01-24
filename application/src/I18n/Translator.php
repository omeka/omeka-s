<?php
namespace Omeka\I18n;

use Laminas\I18n\Translator\TranslatorInterface;
use Omeka\Stdlib\MessageInterface;

class Translator implements TranslatorInterface
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function translate($message, $textDomain = 'default', $locale = null)
    {
        if ($message instanceof MessageInterface) {
            return $message->translate($this->translator, $textDomain, $locale);
        }

        return $this->translator->translate($message, $textDomain, $locale);
    }

    public function translatePlural($singular, $plural, $number, $textDomain = 'default', $locale = null)
    {
        return $this->translator->translatePlural($singular, $plural, $number, $textDomain, $locale);
    }

    /**
     * Get the "real" translator this facade delegates to.
     *
     * @return TranslatorInterface
     */
    public function getDelegatedTranslator()
    {
        return $this->translator;
    }
}
