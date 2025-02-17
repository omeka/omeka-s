<?php
namespace ExtractMetadata\Mapper;

use Doctrine\Common\Collections\Criteria;
use Omeka\Api\Adapter\ResourceTitleHydrator;
use Omeka\Entity;
use Rs\Json\Pointer;

/**
 * Map metadata to media/item values using JSON pointer.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6901 For format specification
 * @see https://github.com/raphaelstolt/php-jsonpointer For PHP implementation
 */
class JsonPointer implements MapperInterface
{
    protected $crosswalk;

    protected $entityManager;

    public function __construct($crosswalk, $entityManager)
    {
        $this->crosswalk = $crosswalk;
        $this->entityManager = $entityManager;
    }

    public function getLabel()
    {
        return 'JSON Pointer';
    }

    public function map(Entity\Media $mediaEntity, array $metadataEntities)
    {
        $itemEntity = $mediaEntity->getItem();
        $mediaValues = $mediaEntity->getValues();
        $itemValues = $itemEntity->getValues();
        $propertiesToClear = ['media' => [], 'item' => []];
        $valuesToAdd = ['media' => [], 'item' => []];
        foreach ($this->crosswalk as $map) {
            if (!isset($map['resource'], $map['extractor'], $map['pointer'], $map['property'], $map['replace'])) {
                // All keys are required.
                continue;
            }

            $resourceType = $map['resource'];
            switch ($resourceType) {
                case 'media':
                    $entity = $mediaEntity;
                    break;
                case 'item':
                    $entity = $itemEntity;
                    break;
                default:
                    // This resource is invalid.
                    continue 2;
            }

            $metadataEntity = current(array_filter($metadataEntities, function ($metadataEntity) use ($map) {
                return $map['extractor'] === $metadataEntity->getExtractor();
            }));
            if (!$metadataEntity) {
                // This extractor has no metadata entity.
                continue;
            }
            $property = $this->entityManager->find('Omeka\Entity\Property', $map['property']);
            if (!$property) {
                // This property does not exist.
                continue;
            }
            try {
                $jsonPointer = new Pointer(json_encode($metadataEntity->getMetadata()));
                $pointerValue = $jsonPointer->get($map['pointer']);
            } catch (\Exception $e) {
                // Invalid JSON, invalid pointer, or nonexistent value.
                continue;
            }

            if (!is_array($pointerValue)) {
                $pointerValue = [$pointerValue];
            }
            foreach ($pointerValue as $valueString) {
                if (!is_string($valueString)) {
                    // The pointer did not resolve to a string.
                    continue;
                }
                $value = new Entity\Value;
                $value->setType('literal');
                $value->setProperty($property);
                $value->setValue($valueString);
                $value->setIsPublic(true);
                $value->setResource($entity);

                $valuesToAdd[$resourceType][] = $value;
                if ($map['replace']) {
                    $propertiesToClear[$resourceType][$property] = $property;
                }
            }
        }
        // Remove values.
        foreach ($propertiesToClear['media'] as $property) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $property));
            foreach ($mediaValues->matching($criteria) as $mediaValue) {
                $mediaValues->removeElement($mediaValue);
            }
        }
        foreach ($propertiesToClear['item'] as $property) {
            $criteria = Criteria::create()->where(Criteria::expr()->eq('property', $property));
            foreach ($itemValues->matching($criteria) as $itemValue) {
                $itemValues->removeElement($itemValue);
            }
        }
        // Add values.
        foreach ($valuesToAdd['media'] as $value) {
            $mediaValues->add($value);
        }
        foreach ($valuesToAdd['item'] as $value) {
            $itemValues->add($value);
        }

        // Update resource titles
        $titlePropertyDql = "SELECT p FROM Omeka\Entity\Property p JOIN p.vocabulary v WHERE p.localName = 'title' AND v.prefix = 'dcterms'";
        $titleProperty = $this->entityManager->createQuery($titlePropertyDql)->getOneOrNullResult();
        $titleHydrator = new ResourceTitleHydrator;
        if ($valuesToAdd['media']) {
            $titleHydrator->hydrate($mediaEntity, $titleProperty);
        }
        if ($valuesToAdd['item']) {
            $titleHydrator->hydrate($itemEntity, $titleProperty);
        }
    }
}
