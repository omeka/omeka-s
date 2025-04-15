<?php

declare(strict_types=1);

namespace Laminas\Validator\Translator;

use Laminas\Translator\TranslatorInterface;

interface TranslatorAwareInterface
{
    /**
     * Sets translator to use in helper
     *
     * @param  TranslatorInterface|null $translator  [optional] translator.
     *             Default is null, which sets no translator.
     * @param  string|null $textDomain  [optional] text domain
     *             Default is null, which skips setTranslatorTextDomain
     */
    public function setTranslator(?TranslatorInterface $translator = null, ?string $textDomain = null): void;

    /**
     * Returns translator used in object
     */
    public function getTranslator(): ?TranslatorInterface;
}
