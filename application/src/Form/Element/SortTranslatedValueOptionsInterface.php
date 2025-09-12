<?php
namespace Omeka\Form\Element;

/**
 * The SortTranslatedValueOptionsInterface interface.
 *
 * The FormSelect view helper translates labels immediately before rendering
 * them, which makes it impossible to sort on the translated labels. Implement
 * this interface to translate before sorting.
 */
interface SortTranslatedValueOptionsInterface
{
    /**
     * Get the finalized value options.
     *
     * Implementing classes should use this method to finalize the structure and
     * content of the value options (e.g. custom sorting).
     */
    public function getFinalizedValueOptions(array $options): array;
}
