<?php declare(strict_types=1);

namespace Common\I18n;

use Common\Stdlib\PsrMessage;
use InvalidArgumentException;
use Omeka\Stdlib\Message;

/**
 * Adaptation of Omeka translator to manage PsrMessage.
 *
 * @throw \InvalidArgumentException
 */
class Translator extends \Omeka\I18n\Translator
{
    public function translate($message, $textDomain = 'default', $locale = null)
    {
        if (is_scalar($message)) {
            return $this->translator->translate((string) $message, $textDomain, $locale);
        } elseif (is_null($message)) {
            return '';
        }

        if (is_object($message)) {
            // Check PsrMessage first because it is more standard.
            if ($message instanceof PsrMessage) {
                // Process translation here to avoid useless sub-call.
                $translation = $this->translator->translate($message->getMessage(), $textDomain, $locale);
                if ($message->hasContext()) {
                    $translation = $message->isSprintFormat()
                        ? sprintf($translation, ...$message->getArgs())
                        : $message->interpolate($translation, $message->getContext());
                }
                return $translation;
            }

            if ($message instanceof Message) {
                $translation = $this->translator->translate($message->getMessage(), $textDomain, $locale);
                if ($message->hasArgs()) {
                    $translation = sprintf($translation, ...$message->getArgs());
                }
                return $translation;
            }

            if (method_exists($message, '__toString')) {
                return $this->translator->translate((string) $message, $textDomain, $locale);
            }
        }

        throw new InvalidArgumentException('A message to translate should be stringable.'); // @translate
    }
}
