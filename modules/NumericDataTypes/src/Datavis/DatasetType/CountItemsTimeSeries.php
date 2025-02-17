<?php
namespace NumericDataTypes\Datavis\DatasetType;

use Datavis\Api\Representation\DatavisVisRepresentation;
use Datavis\DatasetType\AbstractDatasetType;
use DateInterval;
use DatePeriod;
use DateTime;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\ServiceManager\ServiceManager;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Form\Element\PropertySelect;
use Omeka\Job\Exception;

class CountItemsTimeSeries extends AbstractDatasetType
{
    public function getLabel() : string
    {
        return 'Count of items in a time series'; // @translate
    }

    public function getDescription() : ?string
    {
        return 'Visualize the count of items over a selected period of time.'; // @translate
    }

    public function getDiagramTypeNames() : array
    {
        return ['line_chart_time_series', 'histogram_time_series'];
    }

    public function addElements(SiteRepresentation $site, Fieldset $fieldset) : void
    {
        $fieldset->add([
            'type' => PropertySelect::class,
            'name' => 'property_id',
            'options' => [
                'label' => 'Property', // @translate
                'show_required' => true,
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'property_id',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select one…', // @translate
                'required' => false,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Text::class,
            'name' => 'start',
            'options' => [
                'label' => 'Start', // @translate
                'info' => 'Enter the start of the time series in ISO 8601 format: YYYY-MM-DDTHH:MM:SS', // @translate
            ],
            'attributes' => [
                'id' => 'start',
                'placeholder' => 'YYYY-MM-DDTHH:MM:SS',
                'pattern' => '-?\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}',
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Text::class,
            'name' => 'end',
            'options' => [
                'label' => 'End', // @translate
                'info' => 'Enter the end of the time series in ISO 8601 format: YYYY-MM-DDTHH:MM:SS', // @translate
            ],
            'attributes' => [
                'id' => 'end',
                'placeholder' => 'YYYY-MM-DDTHH:MM:SS',
                'pattern' => '-?\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}',
                'required' => true,
            ],
        ]);
        $fieldset->add([
            'type' => Element\Select::class,
            'name' => 'sample_rate',
            'options' => [
                'label' => 'Sample rate', // @translate
                'value_options' => [
                    '1_second' => '1 second', // @translate
                    '1_minute' => '1 minute', // @translate
                    '1_hour' => '1 hour', // @translate
                    '1_day' => '1 day', // @translate
                    '7_days' => '7 days', // @translate
                    '1_month' => '1 month', // @translate
                    '6_months' => '6 months', // @translate
                    '1_year' => '1 year', // @translate
                    '5_years' => '5 years', // @translate
                    '10_years' => '10 years', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'sample_rate',
                'value' => '1_year',
                'required' => true,
            ],
        ]);
    }

    public function getDataset(ServiceManager $services, DatavisVisRepresentation $vis) : array
    {
        $em = $services->get('Omeka\EntityManager');
        $datasetData = $vis->datasetData();

        $start = DateTime::createFromFormat('Y-m-d\TH:i:s', $datasetData['start']);
        $end = DateTime::createFromFormat('Y-m-d\TH:i:s', $datasetData['end']);

        // Get the sample range according to the sample rate.
        switch ($datasetData['sample_rate']) {
            case '1_second':
                $interval = '1 second';
                break;
            case '1_minute':
                $interval = '1 minute';
                break;
            case '1_hour':
                $interval = '1 hour';
                break;
            case '1_day':
                $interval = '1 day';
                break;
            case '7_days':
                $interval = '7 days';
                break;
            case '1_month':
                $interval = '1 month';
                break;
            case '6_months':
                $interval = '6 months';
                break;
            case '1_year':
                $interval = '1 year';
                break;
            case '5_years':
                $interval = '5 years';
                break;
            case '10_years':
                $interval = '10 years';
                break;
            default:
                throw new Exception\InvalidArgumentException('Invalid sample_rate');
        }
        $interval = DateInterval::createFromDateString($interval);
        $period = new DatePeriod($start, $interval, $end);
        $sampleRange = [];
        foreach ($period as $dateTime) {
            $sampleRange[] = $dateTime;
        }

        $dql = '
        SELECT COUNT(DISTINCT t.resource)
        FROM NumericDataTypes\Entity\NumericDataTypesTimestamp t
        WHERE t.resource IN (:item_ids)
        AND t.property = :property_id
        AND t.value >= :start
        AND t.value < :end';
        $query = $em->createQuery($dql);
        $query->setParameter('item_ids', $this->getItemIds($services, $vis));
        $query->setParameter('property_id', $datasetData['property_id']);

        $dataset = [];
        foreach ($sampleRange as $index => $dateTime) {
            if (!isset($sampleRange[$index + 1])) {
                continue; // End on the second to the last datetime.
            }
            $query->setParameter('start', $dateTime->getTimestamp());
            $query->setParameter('end', $sampleRange[$index + 1]->getTimestamp());
            $dataset[] = [
                'label' => $dateTime->format('Y-m-d\TH:i:s'),
                'value' => (int) $query->getSingleScalarResult(),
            ];
        }
        return $dataset;
    }
}
