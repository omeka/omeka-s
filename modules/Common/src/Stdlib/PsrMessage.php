<?php declare(strict_types=1);

namespace Common\Stdlib;

use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;

/**
 * Manage a message with a context list of placeholders formatted as psr-3.
 *
 * Copy of Omeka Message, except the constructor, that requires an array, and
 * the possibility to translate automatically when the translator is enabled.
 * Generally, the translator is not set, as it is usually managed internally.
 *
 * ```
 * // To get a translator in a controller:
 * $translator = $this->translator();
 * // or:
 * $translator = $this->getEvent()->getApplication()->getServiceManager()->get('MvcTranslator');
 * // or:
 * $translator = $this->viewHelpers()->get('translate')->getTranslator();
 *
 * // To get translator in a view:
 * $translator = $this->translator();
 * // or:
 * $translator = $this->plugin('translate')->getTranslator();
 *
 * // To set the translator:
 * $psrMessage->setTranslator($translator);
 * // To disable the translation when the translator is set:
 * $psrMessage->setTranslatorEnabled(false);
 * ```
 *
 * Should not be an extension of \Omeka\Stdlib\Message currently, because
 * another delegator cannot be set for the translator simply.
 * So when the PsrMessage is used in uncommon places (not with messenger or
 * logs), and as long as \Omeka\I18n\Translator doesn't manage PSR-3, the
 * message is interpolated directly, with translation if possible.
 *
 * @todo Set translator on construct.
 * @todo Move PsrMessage into core.
 * @fixme When a translator is set to a message during upgrade, it cannot be displayed via messenger because it cannot be serialized in session: PHP Fatal error:  Uncaught Exception: Serialization of 'Closure' is not allowed in /vendor/laminas/laminas-session/src/SessionManager.php:240.
 *
 * @see \Omeka\Stdlib\Message
 */
class PsrMessage implements \JsonSerializable, PsrInterpolateInterface, TranslatorAwareInterface
{
    use PsrInterpolateTrait;
    use TranslatorAwareTrait;

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var array
     */
    protected $context = [];

    /**
     * @var bool
     */
    protected $escapeHtml = true;

    /**
     * @var bool
     */
    protected $isSprintf = false;

    /**
     * Set the message string and its context.
     *
     * The plural is not managed.
     * The message can be set as PSR-3 (recommended) or C-style (for sprintf).
     *
     * Variadic args allows to simplify upgrade from standard messages.
     */
    public function __construct($message, ...$context)
    {
        // Early cast to string.
        $this->message = (string) $message;
        if (count($context)) {
            if (is_array($context[0])) {
                $this->context = $context[0];
            } else {
                $this->isSprintf = true;
                $this->context = $context;
            }
        }
    }

    /**
     * Get the message string.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the message context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Does this message have context?
     */
    public function hasContext(): bool
    {
        return (bool) $this->context;
    }

    /**
     * Get the message arguments for compatibility purpose only.
     *
     * @deprecated Use hasContext() instead.
     * @return array Non-associative array in order to comply with sprintf.
     */
    public function getArgs()
    {
        // Always use array_values for compatibility.
        return array_values($this->context);
    }

    /**
     * Does this message have arguments? For compatibility purpose only.
     *
     * @deprecated Use hasContext() instead.
     * @return bool
     */
    public function hasArgs()
    {
        return (bool) $this->context;
    }

    public function setEscapeHtml($escapeHtml): self
    {
        $this->escapeHtml = (bool) $escapeHtml;
        return $this;
    }

    /**
     * Get the flag escapeHtml.
     */
    public function getEscapeHtml(): bool
    {
        return $this->escapeHtml;
    }

    /**
     * Get the flag escapeHtml. Kept for compatibility.
     *
     * @deprecated This is a getter, so use getEscapeHtml().
     */
    public function escapeHtml(): bool
    {
        return $this->escapeHtml;
    }

    /**
     * Check if the message uses `sprintf` (old message) or `interpolate` (PSR).
     */
    public function isSprintFormat(): bool
    {
        return $this->isSprintf;
    }

    /**
     * Get the contextualized final message, translated if translator is set.
     *
     * The translation is not done automatically for non-PSR messages, managed
     * with sprintf(), for compatibiity with Message(). Use translate() to force
     * it in that case.
     *
     * @return string
     */
    public function __toString()
    {
        // isSprintf is a compatibility with Message, so no translation is done.
        if ($this->isSprintf) {
            return $this->context
                ? (string) vsprintf($this->message, array_values($this->context))
                : (string) $this->message;
        }
        return $this->isTranslatorEnabled() && $this->hasTranslator()
            ? $this->interpolate($this->translator->translate($this->message), $this->context)
            : $this->interpolate($this->message, $this->context);
    }

    /**
     * Translate the message with the context.
     *
     * Same as TranslatorInterface::translate(), but the message is the current one.
     */
    public function translate($textDomain = 'default', $locale = null): string
    {
        // Check isTranslatorEnabled here? No: the check should be done outside
        // of translate. Anyway, the default value is true and is never checked.
        if ($this->hasTranslator()) {
            return $this->isSprintf
                ? (string) vsprintf($this->translator->translate($this->message, $textDomain, $locale), array_values($this->context))
                : $this->interpolate($this->translator->translate($this->message, $textDomain, $locale), $this->context);
        }
        return $this->isSprintf
            ? (string) vsprintf($this->message, array_values($this->context))
            : $this->interpolate($this->message, $this->context);
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
