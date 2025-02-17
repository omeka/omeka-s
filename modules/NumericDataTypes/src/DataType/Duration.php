<?php
namespace NumericDataTypes\DataType;

use DateInterval;
use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
use NumericDataTypes\Form\Element\Duration as DurationElement;
use Omeka\Entity\Value;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\ValueAnnotatingInterface;
use Laminas\View\Renderer\PhpRenderer;

class Duration extends AbstractDataType implements ValueAnnotatingInterface
{
    /**
     * Seconds in a timespan
     */
    const SECONDS_YEAR = 31536000; // 365 day year
    const SECONDS_MONTH = 2592000; // 30 day month
    const SECONDS_DAY = 86400;
    const SECONDS_HOUR = 3600;
    const SECONDS_MINUTE = 60;

    /**
     * @var array Cache of durations
     */
    protected static $durations = [];

    public function getName()
    {
        return 'numeric:duration';
    }

    public function getLabel()
    {
        return 'Duration'; // @translate
    }

    public function form(PhpRenderer $view)
    {
        $element = new DurationElement('numeric-duration-value');
        $element->getValueElement()->setAttribute('data-value-key', '@value');
        return $view->formElement($element);
    }

    public function isValid(array $valueObject)
    {
        try {
            $this->getDurationFromValue($valueObject['@value']);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
        return true;
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        // Store the duration in ISO 8601, allowing for reduced precision.
        $value->setValue($valueObject['@value']);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value, $options = [])
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return $value->value();
        }
        $duration = $this->getDurationFromValue($value->value());
        $output = [];
        if (null !== $duration['years']) {
            $output[] = (1 === $duration['years'])
                ? sprintf($view->translate('%s year'), $duration['years'])
                : sprintf($view->translate('%s years'), $duration['years']);
        }
        if (null !== $duration['months']) {
            $output[] = (1 === $duration['months'])
                ? sprintf($view->translate('%s month'), $duration['months'])
                : sprintf($view->translate('%s months'), $duration['months']);
        }
        if (null !== $duration['days']) {
            $output[] = (1 === $duration['days'])
                ? sprintf($view->translate('%s day'), $duration['days'])
                : sprintf($view->translate('%s days'), $duration['days']);
        }
        if (null !== $duration['hours']) {
            $output[] = (1 === $duration['hours'])
                ? sprintf($view->translate('%s hour'), $duration['hours'])
                : sprintf($view->translate('%s hours'), $duration['hours']);
        }
        if (null !== $duration['minutes']) {
            $output[] = (1 === $duration['minutes'])
                ? sprintf($view->translate('%s minute'), $duration['minutes'])
                : sprintf($view->translate('%s minutes'), $duration['minutes']);
        }
        if (null !== $duration['seconds']) {
            $output[] = (1 === $duration['seconds'])
                ? sprintf($view->translate('%s second'), $duration['seconds'])
                : sprintf($view->translate('%s seconds'), $duration['seconds']);
        }
        return implode(', ', $output);
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return sprintf('%s %s', $value->value(), $this->render($view, $value));
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        if (!$this->isValid(['@value' => $value->value()])) {
            return ['@value' => $value->value()];
        }
        return [
            '@value' => $value->value(),
            '@type' => 'http://www.w3.org/2001/XMLSchema#duration',
        ];
    }

    public function getEntityClass()
    {
        return 'NumericDataTypes\Entity\NumericDataTypesDuration';
    }

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        $duration = $this->getDurationFromValue($value->getValue());
        $entity->setValue($duration['total_seconds']);
    }

    /**
     * Get the decomposed duration and the total seconds from an ISO 8601
     * duration string. Note that we do not allow fractions or negatives for any
     * parts of a duration, nor do we allow weeks.
     *
     * PHP's DateInterval accepts the ISO 8601 duration spec, but we don't use
     * it here becuase it converts omitted duration components to zero, with no
     * indication that the component was omitted. We'd have to use a regex to
     * decompose the string anyway, so DateInterval would be redundant.
     *
     * Also used to validate the duration string since validation is a side
     * effect of parsing the string.
     *
     * @param string $value
     * @return array
     */
    public static function getDurationFromValue($value)
    {
        if (isset(self::$durations[$value])) {
            return self::$durations[$value];
        }
        // @see https://stackoverflow.com/a/32045167
        $isMatch = preg_match('/^P(?!$)(?:(?<years>\d+)Y)?(?:(?<months>\d+)M)?(?:(?<days>\d+)D)?(T(?=\d)(?:(?<hours>\d+)H)?(?:(?<minutes>\d+)M)?(?:(?<seconds>\d+)S)?)?$/', (string) $value, $matches);
        if (!$isMatch) {
            throw new \InvalidArgumentException('Invalid duration string, must use ISO 8601 without fractions, negatives, or weeks');
        }
        $duration = [
            'years' => (isset($matches['years']) && '' !== $matches['years']) ? (int) $matches['years'] : null,
            'months' => (isset($matches['months']) && '' !== $matches['months']) ? (int) $matches['months'] : null,
            'days' => (isset($matches['days']) && '' !== $matches['days']) ? (int) $matches['days'] : null,
            'hours' => (isset($matches['hours']) && '' !== $matches['hours']) ? (int) $matches['hours'] : null,
            'minutes' => (isset($matches['minutes']) && '' !== $matches['minutes']) ? (int) $matches['minutes'] : null,
            'seconds' => (isset($matches['seconds']) && '' !== $matches['seconds']) ? (int) $matches['seconds'] : null,
        ];
        $duration['years_normalized'] = $duration['years'] ?? 0;
        $duration['months_normalized'] = $duration['months'] ?? 0;
        $duration['days_normalized'] = $duration['days'] ?? 0;
        $duration['hours_normalized'] = $duration['hours'] ?? 0;
        $duration['minutes_normalized'] = $duration['minutes'] ?? 0;
        $duration['seconds_normalized'] = $duration['seconds'] ?? 0;
        // Calculate the total seconds of the duration.
        $totalSeconds =
              ($duration['years_normalized'] * self::SECONDS_YEAR)
            + ($duration['months_normalized'] * self::SECONDS_MONTH)
            + ($duration['days_normalized'] * self::SECONDS_DAY)
            + ($duration['hours_normalized'] * self::SECONDS_HOUR)
            + ($duration['minutes_normalized'] * self::SECONDS_MINUTE)
            + $duration['seconds_normalized'];
        if (Integer::MAX_SAFE_INT < $totalSeconds) {
            throw new \InvalidArgumentException('Invalid duration, exceeds maximum safe integer');
        }
        $duration['total_seconds'] = $totalSeconds;
        self::$durations[$value] = $duration; // Cache the duration
        return $duration;
    }

    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
        if (isset($query['numeric']['dur']['lt']['val'])) {
            $value = $query['numeric']['dur']['lt']['val'];
            $propertyId = $query['numeric']['dur']['lt']['pid'] ?? null;
            if ($this->isValid(['@value' => $value])) {
                $duration = $this->getDurationFromValue($value);
                $number = $duration['total_seconds'];
                $this->addLessThanQuery($adapter, $qb, $propertyId, $number);
            }
        }
        if (isset($query['numeric']['dur']['gt']['val'])) {
            $value = $query['numeric']['dur']['gt']['val'];
            $propertyId = $query['numeric']['dur']['gt']['pid'] ?? null;
            if ($this->isValid(['@value' => $value])) {
                $duration = $this->getDurationFromValue($value);
                $number = $duration['total_seconds'];
                $this->addGreaterThanQuery($adapter, $qb, $propertyId, $number);
            }
        }
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
        if ('duration' === $type) {
            $alias = $adapter->createAlias();
            $qb->addSelect("MIN($alias.value) as HIDDEN numeric_value");
            $qb->leftJoin(
                $this->getEntityClass(), $alias, 'WITH',
                $qb->expr()->andX(
                    $qb->expr()->eq("$alias.resource", 'omeka_root.id'),
                    $qb->expr()->eq("$alias.property", $propertyId)
                )
            );
            $qb->addOrderBy('numeric_value', $query['sort_order']);
        }
    }

    public function valueAnnotationPrepareForm(PhpRenderer $view)
    {
    }

    public function valueAnnotationForm(PhpRenderer $view)
    {
        return $this->form($view);
    }
}
