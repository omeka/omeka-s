<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * Get the configured locale.
 */
class Locale extends AbstractHelper
{
    protected $locale;

    public function __construct($locale)
    {
        // BCP47 specifies that subtags are separated by hyphens.
        $this->locale = str_replace('_', '-', $locale);
    }

    public function __invoke()
    {
        return $this->locale;
    }
}
