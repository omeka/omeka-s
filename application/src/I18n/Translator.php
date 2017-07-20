<?php
namespace Omeka\I18n;

use Omeka\Stdlib\Message;
use Zend\I18n\Translator\TranslatorInterface;

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

    /**
     * {@inheritDoc}
     */
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        if (!$message instanceof Message) {
            return $this->translator->translate($message, $textDomain, $locale);
        }
        $translation = $this->translator->translate($message->getMessage(), $textDomain, $locale);
        if ($message->hasArgs()) {
            $translation = sprintf($translation, ...$message->getArgs());
        }
        return $translation;
    }

    /**
     * {@inheritDoc}
     */
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
