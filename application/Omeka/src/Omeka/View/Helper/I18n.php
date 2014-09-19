<?php
namespace Omeka\View\Helper;

use DateTime;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class i18n extends AbstractHelper
{
    const DATE_FORMAT_NONE   = 'none';
    const DATE_FORMAT_FULL   = 'full';
    const DATE_FORMAT_LONG   = 'long';
    const DATE_FORMAT_MEDIUM = 'medium';
    const DATE_FORMAT_SHORT  = 'short';

    /**
     * @var \Zend\View\HelperPluginManager
     */
    protected $viewHelperManager;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->viewHelperManager = $serviceLocator->get('ViewHelperManager');
    }

    /**
     * Format a date.
     *
     * If PHP's intl extension is not loaded, fall back on default date
     * formatting.
     *
     * @see \Zend\I18n\View\Helper\DateFormat
     * @param DateTime $date
     * @param int $dateType Use self::DATE_FORMAT_* constants
     * @param int $timeType Use self::DATE_FORMAT_* constants
     * @param string $locale Ignored when intl not loaded
     * @param string|null $pattern Ignored when intl not loaded
     * @return string
     */
    public function dateFormat(
        DateTime $date,
        $dateType = self::DATE_FORMAT_MEDIUM,
        $timeType = self::DATE_FORMAT_NONE,
        $locale = null,
        $pattern = null
    ) {
        if (extension_loaded('intl')) {

            // Map local constants to those in IntlDateFormatter.
            $constMap = array(
                self:: DATE_FORMAT_NONE   => \IntlDateFormatter::NONE,
                self:: DATE_FORMAT_FULL   => \IntlDateFormatter::FULL,
                self:: DATE_FORMAT_LONG   => \IntlDateFormatter::LONG,
                self:: DATE_FORMAT_MEDIUM => \IntlDateFormatter::MEDIUM,
                self:: DATE_FORMAT_SHORT  => \IntlDateFormatter::SHORT,
            );
            $dateType = array_key_exists($dateType, $constMap)
                ? $constMap[$dateType]
                : \IntlDateFormatter::MEDIUM;
            $timeType = array_key_exists($timeType, $constMap)
                ? $constMap[$timeType]
                : \IntlDateFormatter::NONE;

            // Proxy to Zend's dateFormat helper.
            $dateFormat = $this->viewHelperManager->get('dateFormat');
            return $dateFormat($date, $dateType, $timeType, $locale, $pattern);
        }

        // Set the date format.
        $dateFormat = '';
        switch ($dateType) {
            case self::DATE_FORMAT_NONE:
                break;
            case self::DATE_FORMAT_FULL:
                $dateFormat .= 'l, F j, Y';
                break;
            case self::DATE_FORMAT_LONG:
                $dateFormat .= 'F j, Y';
                break;
            case self::DATE_FORMAT_SHORT:
                $dateFormat .= 'n/j/y';
                break;
            case self::DATE_FORMAT_MEDIUM:
            default:
                $dateFormat .= 'M j, Y';
                break;
        }

        // Set the time format.
        $timeFormat = '';
        switch ($timeType) {
            case self::DATE_FORMAT_FULL:
                $timeFormat .= 'g:i:sa T';
                break;
            case self::DATE_FORMAT_LONG:
                $timeFormat .= 'g:i:sa';
                break;
            case self::DATE_FORMAT_MEDIUM:
                $timeFormat .= 'g:ia';
                break;
            case self::DATE_FORMAT_SHORT:
                $timeFormat .= 'g:ia';
                break;
            case self::DATE_FORMAT_NONE:
            default:
                break;
        }

        return $date->format(trim("$dateFormat $timeFormat"));
    }
}
