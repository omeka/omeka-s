<?php declare(strict_types=1);

namespace Omeka\Form\Element;

use Laminas\Form\Element\Password;

/**
 * A setting field holding a secret (API key, password) encrypted at rest.
 *
 * The stored value is never rendered back to the browser:
 * - empty means that a value is already set (a placeholder may be set);
 * - an empty submission keep the current value.
 *
 * Rendered as a text input with a lock icon, excluded from browser autofill;
 * set option "masked" to true to render a password input instead.
 * @see \Omeka\Form\View\Helper\FormSecret
 */
class Secret extends Password
{
}
