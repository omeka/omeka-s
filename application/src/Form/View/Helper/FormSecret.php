<?php
namespace Omeka\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\AbstractHelper;

/**
 * Render a secret input with a clear control as a single element.
 *
 * The stored value is never rendered back to the browser:
 * - empty means that a value is already set (a placeholder may be set);
 * - an empty submission keep the current value.
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
        $input = $view->formPassword($element);

        if (!$element->getOption('has_value')) {
            return $this->style() . $input;
        }

        $remove = $view->escapeHtmlAttr($element->getName() . '_remove');
        $label = $view->escapeHtml($view->translate('Remove the saved value')); // @translate
        return $this->style()
            . <<<HTML
                <span class="secret-element">
                    $input
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
                .secret-element { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; }
                .secret-element-remove { font-weight:normal; font-size:.9em; color:#6c757d; }
            </style>
            HTML;
    }
}
