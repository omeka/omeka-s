<?php declare(strict_types=1);

namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\AbstractHelper;

/**
 * Render a secret input with a lock icon and an optional remove control.
 *
 * The stored value is never rendered back to the browser:
 * - empty means that a value is already set (a placeholder may be set);
 * - an empty submission keep the current value.
 *
 * The input is a plain text field by default. Set option "masked" to true to
 * render a password input instead.
 */
class FormSecret extends AbstractHelper
{
    /**
     * @var bool Avoid to copy multiple times the inline style.
     */
    private static $styleEmitted = false;

    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();

        // Never echo the stored secret back to the browser.
        $element->setValue('');
        if (!$element->getAttribute('autocomplete')) {
            $element->setAttribute('autocomplete', 'off');
        }

        // When a value is saved, replace any placeholder with a hint that an
        // empty submission keeps the current value, so the placeholder does not
        // look like a missing value.
        $hasValue = (bool) $element->getOption('has_value');
        if ($hasValue) {
            $element->setAttribute('placeholder', $view->translate('Leave empty to keep the current value')); // @translate
        }

        // Render a masked password if requested.
        $input = $element->getOption('masked')
            ? $view->formPassword($element)
            : $view->formText($element);

        // A lock icon is displayed to mark field as a secret (gray or green).
        $iconTitle = $view->escapeHtmlAttr($hasValue
            ? $view->translate('A value is already saved') // @translate
            : $view->translate('This value is secret')); // @translate
        $iconClass = $hasValue
            ? 'secret-element-icon secret-element-icon--set'
            : 'secret-element-icon';
        $field = <<<HTML
            <span class="secret-element-field">
                $input
                <span class="$iconClass o-icon-lock" role="img" title="$iconTitle" aria-label="$iconTitle"></span>
            </span>
            HTML;

        if (!$hasValue) {
            return $this->style() . $field;
        }

        // When a value is saved, add a checkbox to remove it.
        $name = $element->getName();
        $remove = $view->escapeHtmlAttr(substr($name, -1) === ']'
            ? substr($name, 0, -1) . '_remove]'
            : $name . '_remove');
        $label = $view->escapeHtml($view->translate('Remove the saved value')); // @translate
        return $this->style()
            . <<<HTML
                <span class="secret-element">
                    $field
                    <label class="secret-element-remove">
                        <input type="checkbox" name="$remove" value="1">
                        $label
                    </label>
                </span>
                HTML;
    }

    protected function style()
    {
        if (self::$styleEmitted) {
            return '';
        }

        self::$styleEmitted = true;

        return <<<'HTML'
            <style>
                .secret-element { display:flex; flex-wrap:nowrap; align-items:center; gap:.5rem; width:100%; }
                .secret-element-field { position:relative; flex:1 1 auto; min-width:0; }
                .secret-element-field input { width:100%; box-sizing:border-box; padding-inline-end:1.9rem; }
                .secret-element-icon { position:absolute; inset-inline-end:.5rem; top:50%; transform:translateY(-50%); display:inline-flex; color:#6c757d; pointer-events:none; }
                .secret-element-icon--set { color:#198754; }
                .secret-element .secret-element-remove { flex:none; width:auto; margin:0; white-space:nowrap; font-weight:normal; font-size:.9em; color:#6c757d; }
            </style>
            HTML;
    }
}
