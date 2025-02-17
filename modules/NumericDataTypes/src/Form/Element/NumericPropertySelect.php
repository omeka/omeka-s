<?php
namespace NumericDataTypes\Form\Element;

use Doctrine\ORM\EntityManager;
use Laminas\Form\Element\Select;

class NumericPropertySelect extends Select
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return ApiManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Get value options for template properties of numeric data types.
     *
     * @return array
     */
    public function getValueOptions() : array
    {
        $dataTypes = $this->getOption('numeric_data_type');
        $disambiguate = $this->getOption('numeric_data_type_disambiguate');

        if (!is_array($dataTypes)) {
            $dataTypes = [$dataTypes];
        }
        if (!$dataTypes) {
            return [];
        }

        // Users don't pass the full numeric data type names using the
        // numeric_data_type option, so set them here.
        $numericDataTypes = [];
        foreach ($dataTypes as $dataType) {
            $numericDataTypes[sprintf('numeric:%s', $dataType)] = true;
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('rtp')
            ->from('Omeka\Entity\ResourceTemplateProperty', 'rtp')
            ->andWhere($qb->expr()->isNotNull('rtp.dataType'));
        $query = $qb->getQuery();
        $valueOptions = [];
        foreach ($query->getResult() as $templateProperty) {
            $property = $templateProperty->getProperty();
            $template = $templateProperty->getResourceTemplate();
            foreach ($templateProperty->getDataType() ?? [] as $dataType) {
                if (!isset($numericDataTypes[$dataType])) {
                    // This is not a requested numeric data type.
                    continue;
                }
                $value = $disambiguate
                    ? sprintf('%s:%s', $dataType, $property->getId())
                    : $property->getId();
                $label = $disambiguate
                    ? sprintf('%s (%s)', $property->getLabel(), $dataType)
                    : $property->getLabel();
                if (!isset($valueOptions[$value])) {
                    $valueOptions[$value] = [
                        'label' => $label,
                        'value' => $value,
                        'template_labels' => [],
                    ];
                }
                $templateLabel = $disambiguate
                    ? sprintf(
                        '• %s: %s',
                        $template->getLabel(),
                        $templateProperty->getAlternateLabel() ?: $property->getLabel()
                    )
                    : sprintf(
                        '• %s: %s (%s)',
                        $template->getLabel(),
                        $templateProperty->getAlternateLabel() ?: $property->getLabel(),
                        $dataType
                    );
                // More than one template could use the same property.
                $valueOptions[$value]['template_labels'][] = $templateLabel;
            }
        }

        // Include template/property labels in the option title attribute.
        foreach ($valueOptions as $value => $option) {
            $templateLabels = $option['template_labels'];
            $valueOptions[$value]['attributes']['title'] = implode("\n", $templateLabels);
        }

        // Sort options alphabetically.
        usort($valueOptions, function ($a, $b) {
            return strcasecmp($a['label'], $b['label']);
        });
        return $valueOptions;
    }
}
