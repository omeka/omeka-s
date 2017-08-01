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
        $this->locale = $locale;
    }

    public function __invoke()
    {
        return $this->locale;
    }
}
