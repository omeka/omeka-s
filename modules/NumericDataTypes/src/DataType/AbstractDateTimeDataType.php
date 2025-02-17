<?php
namespace NumericDataTypes\DataType;

use DateTime;
use DateTimeZone;
use IntlCalendar;
use IntlDateFormatter;
use IntlDatePatternGenerator;
use InvalidArgumentException;

abstract class AbstractDateTimeDataType extends AbstractDataType
{
    /**
     * Minimum and maximum years.
     *
     * When converted to Unix timestamps, anything outside this range would
     * exceed the minimum or maximum range for a 64-bit integer.
     */
    const YEAR_MIN = -292277022656;
    const YEAR_MAX = 292277026595;

    /**
     * ISO 8601 datetime pattern
     *
     * The standard permits the expansion of the year representation beyond
     * 0000–9999, but only by prior agreement between the sender and the
     * receiver. Given that our year range is unusually large we shouldn't
     * require senders to zero-pad to 12 digits for every year. Users would have
     * to a) have prior knowledge of this unusual requirement, and b) convert
     * all existing ISO strings to accommodate it. This is needlessly
     * inconvenient and would be incompatible with most other systems. Instead,
     * we require the standard's zero-padding to 4 digits, but stray from the
     * standard by accepting non-zero padded integers beyond -9999 and 9999.
     *
     * Note that we only accept ISO 8601's extended format: the date segment
     * must include hyphens as separators, and the time and offset segments must
     * include colons as separators. This follows the standard's best practices,
     * which notes that "The basic format should be avoided in plain text."
     */
    const PATTERN_ISO8601 = '^(?<date>(?<year>-?\d{4,})(-(?<month>\d{2}))?(-(?<day>\d{2}))?)(?<time>(T(?<hour>\d{2}))?(:(?<minute>\d{2}))?(:(?<second>\d{2}))?)(?<offset>((?<offset_hour>[+-]\d{2})?(:(?<offset_minute>\d{2}))?)|Z?)$';

    /**
     * @var array Cache of date/times
     */
    protected static $dateTimes = [];

    /**
     * Get relevant date/time information from an ISO 8601 value.
     *
     * Sets the decomposed date/time, format patterns, and the DateTime and
     * IntlCalendar objects to an array and returns the array.
     *
     * Use $defaultFirst to set the default of each datetime component to its
     * first (true) or last (false) possible integer, if the specific component
     * is not passed with the value.
     *
     * Also used to validate the datetime since validation is a side effect of
     * parsing the value into its component datetime pieces.
     *
     * @throws InvalidArgumentException
     * @param string $value An ISO 8601 string
     * @param bool $defaultFirst
     * @return array
     */
    public static function getDateTimeFromValue($value, $defaultFirst = true)
    {
        if (isset(self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'])) {
            return self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'];
        }

        // Match against ISO 8601, allowing for reduced accuracy.
        $isMatch = preg_match(sprintf('/%s/', self::PATTERN_ISO8601), (string) $value, $matches);
        if (!$isMatch) {
            throw new InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        $matches = array_filter($matches); // remove empty values
        // An hour requires a day.
        if (isset($matches['hour']) && !isset($matches['day'])) {
            throw new InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }
        // An offset requires a time.
        if (isset($matches['offset']) && !isset($matches['time'])) {
            throw new InvalidArgumentException(sprintf('Invalid ISO 8601 datetime: %s', $value));
        }

        // Set the datetime components included in the passed value.
        $dateTime = [
            'value' => $value,
            'date_value' => $matches['date'],
            'time_value' => $matches['time'] ?? null,
            'offset_value' => $matches['offset'] ?? null,
            'year' => (int) $matches['year'],
            'month' => isset($matches['month']) ? (int) $matches['month'] : null,
            'day' => isset($matches['day']) ? (int) $matches['day'] : null,
            'hour' => isset($matches['hour']) ? (int) $matches['hour'] : null,
            'minute' => isset($matches['minute']) ? (int) $matches['minute'] : null,
            'second' => isset($matches['second']) ? (int) $matches['second'] : null,
            'offset_hour' => isset($matches['offset_hour']) ? (int) $matches['offset_hour'] : null,
            'offset_minute' => isset($matches['offset_minute']) ? (int) $matches['offset_minute'] : null,
        ];

        // Set the normalized datetime components. Each component not included
        // in the passed value is given a default value.
        $dateTime['month_normalized'] = $dateTime['month'] ?? ($defaultFirst ? 1 : 12);
        // The last day takes special handling, as it depends on year/month.
        $dateTime['day_normalized'] = $dateTime['day']
            ?? ($defaultFirst ? 1 : self::getLastDay($dateTime['year'], $dateTime['month_normalized']));
        $dateTime['hour_normalized'] = $dateTime['hour'] ?? ($defaultFirst ? 0 : 23);
        $dateTime['minute_normalized'] = $dateTime['minute'] ?? ($defaultFirst ? 0 : 59);
        $dateTime['second_normalized'] = $dateTime['second'] ?? ($defaultFirst ? 0 : 59);
        $dateTime['offset_hour_normalized'] = $dateTime['offset_hour'] ?? 0;
        $dateTime['offset_minute_normalized'] = $dateTime['offset_minute'] ?? 0;
        // Set the UTC offset (+00:00) if no offset is provided.
        $dateTime['offset_normalized'] = isset($dateTime['offset_value'])
            ? ('Z' === $dateTime['offset_value'] ? '+00:00' : $dateTime['offset_value'])
            : '+00:00';

        // Validate ranges of the datetime component.
        if ((self::YEAR_MIN > $dateTime['year']) || (self::YEAR_MAX < $dateTime['year'])) {
            throw new InvalidArgumentException(sprintf('Invalid year: %s', $dateTime['year']));
        }
        if ((1 > $dateTime['month_normalized']) || (12 < $dateTime['month_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid month: %s', $dateTime['month_normalized']));
        }
        if ((1 > $dateTime['day_normalized']) || (31 < $dateTime['day_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid day: %s', $dateTime['day_normalized']));
        }
        if ((0 > $dateTime['hour_normalized']) || (23 < $dateTime['hour_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid hour: %s', $dateTime['hour_normalized']));
        }
        if ((0 > $dateTime['minute_normalized']) || (59 < $dateTime['minute_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid minute: %s', $dateTime['minute_normalized']));
        }
        if ((0 > $dateTime['second_normalized']) || (59 < $dateTime['second_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid second: %s', $dateTime['second_normalized']));
        }
        if ((-23 > $dateTime['offset_hour_normalized']) || (23 < $dateTime['offset_hour_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid hour offset: %s', $dateTime['offset_hour_normalized']));
        }
        if ((0 > $dateTime['offset_minute_normalized']) || (59 < $dateTime['offset_minute_normalized'])) {
            throw new InvalidArgumentException(sprintf('Invalid minute offset: %s', $dateTime['offset_minute_normalized']));
        }

        // Set the ISO 8601 format and render format.
        if (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second']) && isset($dateTime['offset_value'])) {
            $formatIso8601 = 'Y-m-d\TH:i:sP';
            $formatRender = 'j F Y H:i:s P';
            $formatRenderIntl = 'd LLLL y G, HH:mm:ss xxx';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['offset_value'])) {
            $formatIso8601 = 'Y-m-d\TH:iP';
            $formatRender = 'j F Y H:i P';
            $formatRenderIntl = 'd LLLL y G, HH:mm xxx';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['offset_value'])) {
            $formatIso8601 = 'Y-m-d\THP';
            $formatRender = 'j F Y H P';
            $formatRenderIntl = 'd LLLL y G, HH xxx';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute']) && isset($dateTime['second'])) {
            $formatIso8601 = 'Y-m-d\TH:i:s';
            $formatRender = 'j F Y H:i:s';
            $formatRenderIntl = 'd LLLL y G, HH:mm:ss';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour']) && isset($dateTime['minute'])) {
            $formatIso8601 = 'Y-m-d\TH:i';
            $formatRender = 'j F Y H:i';
            $formatRenderIntl = 'd LLLL y G, HH:mm';
        } elseif (isset($dateTime['month']) && isset($dateTime['day']) && isset($dateTime['hour'])) {
            $formatIso8601 = 'Y-m-d\TH';
            $formatRender = 'j F Y H';
            $formatRenderIntl = 'd LLLL y G, HH:mm';
        } elseif (isset($dateTime['month']) && isset($dateTime['day'])) {
            $formatIso8601 = 'Y-m-d';
            $formatRender = 'j F Y';
            $formatRenderIntl = 'd LLLL y G';
        } elseif (isset($dateTime['month'])) {
            $formatIso8601 = 'Y-m';
            $formatRender = 'F Y';
            $formatRenderIntl = 'LLLL y G';
        } else {
            $formatIso8601 = 'Y';
            $formatRender = 'Y';
            $formatRenderIntl = 'y G';
        }
        $dateTime['format_iso8601'] = $formatIso8601;
        $dateTime['format_render'] = $formatRender;
        $dateTime['format_render_intl'] = $formatRenderIntl;

        // Set the DateTime object.
        $dateTime['date'] = new DateTime('now', new DateTimeZone($dateTime['offset_normalized']));
        $dateTime['date']->setDate(
            $dateTime['year'],
            $dateTime['month_normalized'],
            $dateTime['day_normalized']
        )->setTime(
            $dateTime['hour_normalized'],
            $dateTime['minute_normalized'],
            $dateTime['second_normalized']
        );

        self::$dateTimes[$value][$defaultFirst ? 'first' : 'last'] = $dateTime; // Cache the date/time
        return $dateTime;
    }

    /**
     * Get a formatted (human-readable) date/time from an ISO 8601 value.
     *
     * Uses the standard DateTime format if the intl extension is not loaded or
     * the date is outside bounds. (Note that IntlCalendar only supports range
     * ~5.8M BCE to ~5.8M CE.) Otherwise this uses IntlDateFormatter and
     * IntlCalendar to localize the date/time.
     *
     * Use $defaultFirst to set the default of each datetime component to its
     * first (true) or last (false) possible integer, if the specific component
     * is not passed with the value.
     *
     * @see https://unicode-org.github.io/icu-docs/apidoc/dev/icu4j/com/ibm/icu/util/Calendar.html
     * @param string $value An ISO 8601 string
     * @param bool $defaultFirst
     * @param ?string $locale
     * @return string
     */
    public static function getFormattedDateTimeFromValue($value, $defaultFirst = true, $options = [])
    {
        $dateTime = self::getDateTimeFromValue($value, $defaultFirst);

        $isOutsideBounds = ((5800000 < $dateTime['year']) || (-5800000 > $dateTime['year']));
        if (!extension_loaded('intl') || $isOutsideBounds) {
            return $dateTime['date']->format($dateTime['format_render']);
        }

        // Configure IntlDateFormatter.
        $intlDateFormatter = new IntlDateFormatter(
            $options['lang'] ?? null,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $dateTime['offset_value'] ? sprintf('GMT%s', $dateTime['offset_normalized']) : null
        );
        // PHP 8.1 is required to use IntlDatePatternGenerator to get the best
        // date pattern for the given locale. Otherwise, use the default pattern.
        if (version_compare(phpversion(), '8.1', '>=')) {
            $intlDatePatternGenerator = new IntlDatePatternGenerator($options['lang'] ?? null);
            $format = $intlDatePatternGenerator->getBestPattern($dateTime['format_render_intl']);
        } else {
            $format = $dateTime['format_render_intl'];
        }
        if (0 <= $dateTime['year']) {
            // No need to include the era for positive years. It is implied.
            $format = str_replace([' G', 'G '], '', $format);
        } else {
            // IntlDateFormatter substracts one year for negative years because
            // year 0 doesn't exist, so it is added for display.
            ++$dateTime['year'];
        }
        $intlDateFormatter->setPattern($format);

        // Configure IntlCalendar.
        $intlCalendar = IntlCalendar::createInstance(
            $dateTime['offset_value'] ? sprintf('GMT%s', $dateTime['offset_normalized']) : null
        );
        $intlCalendar->set(
            $dateTime['year'],
            $dateTime['month_normalized'] - 1, // IntlCalendar months are zero indexed
            $dateTime['day_normalized'],
            $dateTime['hour_normalized'],
            $dateTime['minute_normalized'],
            $dateTime['second_normalized']
        );

        return $intlDateFormatter->format($intlCalendar);
    }

    /**
     * Get the last day of a given year/month.
     *
     * @param int $year
     * @param int $month
     * @return int
     */
    public static function getLastDay($year, $month)
    {
        switch ($month) {
            case 2:
                // February (accounting for leap year)
                $leapYear = date('L', mktime(0, 0, 0, 1, 1, $year));
                return $leapYear ? 29 : 28;
            case 4:
            case 6:
            case 9:
            case 11:
                // April, June, September, November
                return 30;
            default:
                // January, March, May, July, August, October, December
                return 31;
        }
    }
}
