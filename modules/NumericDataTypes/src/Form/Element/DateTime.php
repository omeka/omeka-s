<?php
namespace NumericDataTypes\Form\Element;

use NumericDataTypes\DataType\Timestamp as TimestampDataType;
use Laminas\Form\Element;

class DateTime extends Element
{
    protected $valueElement;
    protected $yearElement;
    protected $monthElement;
    protected $dayElement;
    protected $hourElement;
    protected $minuteElement;
    protected $secondElement;
    protected $offsetElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->valueElement = (new Element\Hidden($name))
            ->setAttribute('class', 'numeric-datetime-value to-require');
        $this->yearElement = (new Element\Number('year'))
            ->setAttributes([
                'class' => 'numeric-datetime-year',
                'step' => 1,
                'min' => TimestampDataType::YEAR_MIN,
                'max' => TimestampDataType::YEAR_MAX,
                'placeholder' => 'Year', // @translate
                'aria-label' => 'Year', // @translate
            ]);
        $this->monthElement = (new Element\Select('month'))
            ->setAttribute('class', 'numeric-datetime-month')
            ->setEmptyOption('Month') // @translate
            ->setAttribute('aria-label', 'Month') // @translate
            ->setValueOptions($this->getMonthValueOptions());
        $this->dayElement = (new Element\Select('day'))
            ->setAttribute('class', 'numeric-datetime-day')
            ->setEmptyOption('Day') // @translate
            ->setAttribute('aria-label', 'Day') // @translate
            ->setValueOptions($this->getDayValueOptions());
        $this->hourElement = (new Element\Select('hour'))
            ->setAttribute('class', 'numeric-datetime-hour')
            ->setEmptyOption('Hour') // @translate
            ->setAttribute('aria-label', 'Hour') // @translate
            ->setValueOptions($this->getHourValueOptions());
        $this->minuteElement = (new Element\Select('minute'))
            ->setAttribute('class', 'numeric-datetime-minute')
            ->setEmptyOption('Minute') // @translate
            ->setAttribute('aria-label', 'Minute') // @translate
            ->setValueOptions($this->getMinuteSecondValueOptions());
        $this->secondElement = (new Element\Select('second'))
            ->setAttribute('class', 'numeric-datetime-second')
            ->setEmptyOption('Second') // @translate
            ->setAttribute('aria-label', 'Second') // @translate
            ->setValueOptions($this->getMinuteSecondValueOptions());
        $this->offsetElement = (new Element\Select('offset'))
            ->setAttribute('class', 'numeric-datetime-offset')
            ->setEmptyOption('Offset') // @translate
            ->setAttribute('aria-label', 'Offset') // @translate
            ->setValueOptions($this->getOffsetValueOptions());
    }

    public function getMonthValueOptions()
    {
        return [
            1 => '01 — January', // @translate
            2 => '02 — February', // @translate
            3 => '03 — March', // @translate
            4 => '04 — April', // @translate
            5 => '05 — May', // @translate
            6 => '06 — June', // @translate
            7 => '07 — July', // @translate
            8 => '08 — August', // @translate
            9 => '09 — September', // @translate
            10 => '10 — October', // @translate
            11 => '11 — November', // @translate
            12 => '12 — December', // @translate
        ];
    }

    public function getDayValueOptions()
    {
        return array_combine(
            range(1, 31),
            array_map(function ($n) {
                return sprintf('%02d', $n);
            }, range(1, 31))
        );
    }

    public function getHourValueOptions()
    {
        return [
            0 => '00 — 12 am', // @translate
            1 => '01 — 1 am', // @translate
            2 => '02 — 2 am', // @translate
            3 => '03 — 3 am', // @translate
            4 => '04 — 4 am', // @translate
            5 => '05 — 5 am', // @translate
            6 => '06 — 6 am', // @translate
            7 => '07 — 7 am', // @translate
            8 => '08 — 8 am', // @translate
            9 => '09 — 9 am', // @translate
            10 => '10 — 10 am', // @translate
            11 => '11 — 11 am', // @translate
            12 => '12 — 12 pm', // @translate
            13 => '13 — 1 pm', // @translate
            14 => '14 — 2 pm', // @translate
            15 => '15 — 3 pm', // @translate
            16 => '16 — 4 pm', // @translate
            17 => '17 — 5 pm', // @translate
            18 => '18 — 6 pm', // @translate
            19 => '19 — 7 pm', // @translate
            20 => '20 — 8 pm', // @translate
            21 => '21 — 9 pm', // @translate
            22 => '22 — 10 pm', // @translate
            23 => '23 — 11 pm', // @translate
        ];
    }

    public function getMinuteSecondValueOptions()
    {
        return array_map(function ($n) {
            return sprintf('%02d', $n);
        }, range(0, 59));
    }

    public function getOffsetValueOptions()
    {
        // UTC offsets taken from https://en.wikipedia.org/wiki/List_of_UTC_time_offsets
        $offsets = [
            '-12:00',
            '-11:00',
            '-10:00',
            '-09:30',
            '-09:00',
            '-08:00',
            '-07:00',
            '-06:00',
            '-05:00',
            '-04:00',
            '-03:30',
            '-03:00',
            '-02:00',
            '-01:00',
            '+00:00',
            '+01:00',
            '+02:00',
            '+03:00',
            '+03:30',
            '+04:00',
            '+04:30',
            '+05:00',
            '+05:30',
            '+05:45',
            '+06:00',
            '+06:30',
            '+07:00',
            '+08:00',
            '+08:45',
            '+09:00',
            '+09:30',
            '+10:00',
            '+10:30',
            '+11:00',
            '+12:00',
            '+12:45',
            '+13:00',
            '+14:00',
        ];
        return array_combine($offsets, $offsets);
    }

    public function getValueElement()
    {
        $this->valueElement->setValue($this->getValue());
        return $this->valueElement;
    }

    public function getYearElement()
    {
        return $this->yearElement;
    }

    public function getMonthElement()
    {
        return $this->monthElement;
    }

    public function getDayElement()
    {
        return $this->dayElement;
    }

    public function getHourElement()
    {
        return $this->hourElement;
    }

    public function getMinuteElement()
    {
        return $this->minuteElement;
    }

    public function getSecondElement()
    {
        return $this->secondElement;
    }

    public function getOffsetElement()
    {
        return $this->offsetElement;
    }
}
