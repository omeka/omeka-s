<?php
namespace Omeka\Form\Element;

/**
 * The SelectSortInterface interface.
 *
 * The FormSelect view helper translates labels immediately before rendering
 * them, which makes it impossible to sort on the translated labels. Implement
 * this interface to translate before sorting.
 */
interface SelectSortInterface
{
    public function translateValueOptions(): bool;

    /**
     * Get the finalized value options.
     *
     * Implementing classes may use this method to finalize the structure and
     * content of the value options (e.g. custom sorting).
     */
    public function finalizeValueOptions(array $options): array;
}
