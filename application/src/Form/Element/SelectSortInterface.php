<?php
namespace Omeka\Form\Element;

/**
 * The SelectSortInterface interface.
 *
 * Select elements can implement this interface to enable features for sorting
 * and translating value options. By default, options will be translated first
 * and then sorted, which is not possible using Laminas's FormSelect helper.
 *
 * @see Omeka\Form\Element\SelectSortTrait
 * @see Omeka\Form\View\Helper\FormSelect
 */
interface SelectSortInterface
{
    /**
     * Translate value options?
     *
     * Implementing classes should return whether to translate labels before
     * sorting.
     */
    public function translateValueOptions(): bool;

    /**
     * Get the finalized value options.
     *
     * Implementing classes may use this method to finalize the structure and
     * content of the value options (e.g. custom sorting).
     */
    public function finalizeValueOptions(array $options): array;
}
