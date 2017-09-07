<?php
namespace Omeka\View\Helper;

use DateTime;
use Zend\I18n\View\Helper\DateFormat;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering localized data.
 */
class i18n extends AbstractHelper
{
    const DATE_FORMAT_NONE = 'none';
    const DATE_FORMAT_FULL = 'full';
    const DATE_FORMAT_LONG = 'long';
    const DATE_FORMAT_MEDIUM = 'medium';
    const DATE_FORMAT_SHORT = 'short';

    /**
     * @var string
     */
    protected $timezone;

    /**
     * @var DateFormat|null
     */
    protected $dateFormatHelper;

    /**
     * Construct the helper.
     *
     * @param string $timezone
     */
    public function __construct($timezone, DateFormat $dateFormatHelper = null)
    {
        $this->timezone = $timezone;
        if ($dateFormatHelper) {
            $this->dateFormatHelper = $dateFormatHelper->setTimezone($this->timezone);
        }
    }

    /**
     * Format a date.
     *
     * If PHP's intl extension is not loaded, this helper will fall back on a
     * predefined date and time format.
     *
     * @param DateTime $date
     * @param string $dateType Use local DATE_FORMAT_* constants, not their
     *     corresponding constants in IntlDateFormatter.
     * @param string $timeType Use local DATE_FORMAT_* constants, not their
     *     corresponding constants in IntlDateFormatter.
     * @param string|null $locale Optional locale to use when formatting or
     *     parsing. Ignored when intl extension is not loaded.
     * @param string|null $pattern Optional pattern to use when formatting or
     *     parsing. Possible patterns are documented at
     *     {@link http://userguide.icu-project.org/formatparse/datetime}.
     *     Ignored when intl extension is not loaded.
     * @return string
     */
    public function dateFormat(
        DateTime $date = null,
        $dateType = self::DATE_FORMAT_MEDIUM,
        $timeType = self::DATE_FORMAT_NONE,
        $locale = null,
        $pattern = null
    ) {
        if (!$date) {
            return null;
        }

        if ($this->dateFormatHelper) {

            // Map local constants to those in IntlDateFormatter.
            $constMap = [
                self:: DATE_FORMAT_NONE => \IntlDateFormatter::NONE,
                self:: DATE_FORMAT_FULL => \IntlDateFormatter::FULL,
                self:: DATE_FORMAT_LONG => \IntlDateFormatter::LONG,
                self:: DATE_FORMAT_MEDIUM => \IntlDateFormatter::MEDIUM,
                self:: DATE_FORMAT_SHORT => \IntlDateFormatter::SHORT,
            ];
            $dateType = array_key_exists($dateType, $constMap)
                ? $constMap[$dateType]
                : \IntlDateFormatter::MEDIUM;
            $timeType = array_key_exists($timeType, $constMap)
                ? $constMap[$timeType]
                : \IntlDateFormatter::NONE;

            // Proxy to Zend's dateFormat helper.
            return $this->dateFormatHelper->__invoke($date, $dateType, $timeType, $locale, $pattern);
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

        // Clone the date object to prevent the timezone change from leaking.
        $date = clone $date;
        return $date->setTimezone(new \DateTimeZone($this->timezone))
            ->format(trim("$dateFormat $timeFormat"));
    }
}
