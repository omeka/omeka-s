<?php
namespace NumericDataTypes\Form\Element;

use Laminas\Form\Element;

class Duration extends Element
{
    protected $valueElement;
    protected $yearsElement;
    protected $monthsElement;
    protected $daysElement;
    protected $hoursElement;
    protected $minutesElement;
    protected $secondsElement;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, $options);

        $this->valueElement = new Element\Hidden($name);
        $this->valueElement->setAttributes([
            'class' => 'numeric-duration-value to-require',
        ]);

        $this->yearsElement = new Element\Number('years');
        $this->yearsElement->setAttributes([
            'class' => 'numeric-duration-years',
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Years', // @translate
            'aria-label' => 'Years', // @translate
        ]);

        $this->monthsElement = new Element\Number('months');
        $this->monthsElement->setAttributes([
            'class' => 'numeric-duration-months',
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Months', // @translate
            'aria-label' => 'Months', // @translate
        ]);

        $this->daysElement = new Element\Number('days');
        $this->daysElement->setAttributes([
            'class' => 'numeric-duration-days',
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Days', // @translate
            'aria-label' => 'Days', // @translate
        ]);

        $this->hoursElement = new Element\Number('hours');
        $this->hoursElement->setAttributes([
            'class' => 'numeric-duration-hours',
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Hours', // @translate
            'aria-label' => 'Hours', // @translate
        ]);

        $this->minutesElement = new Element\Number('minutes');
        $this->minutesElement->setAttributes([
            'class' => 'numeric-duration-minutes',
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Minutes', // @translate
            'aria-label' => 'Minutes', // @translate
        ]);

        $this->secondsElement = new Element\Number('seconds');
        $this->secondsElement->setAttributes([
            'class' => 'numeric-duration-seconds',
            'step' => 1,
            'min' => 0,
            'placeholder' => 'Seconds', // @translate
            'aria-label' => 'Seconds', // @translate
        ]);
    }

    public function getValueElement()
    {
        $this->valueElement->setValue($this->getValue());
        return $this->valueElement;
    }

    public function getYearsElement()
    {
        return $this->yearsElement;
    }

    public function getMonthsElement()
    {
        return $this->monthsElement;
    }

    public function getDaysElement()
    {
        return $this->daysElement;
    }

    public function getHoursElement()
    {
        return $this->hoursElement;
    }

    public function getMinutesElement()
    {
        return $this->minutesElement;
    }

    public function getSecondsElement()
    {
        return $this->secondsElement;
    }
}
