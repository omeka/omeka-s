<?php
namespace Mapping\Osii\ResourceMapper;

use Exception;
use Osii\ResourceMapper\AbstractResourceMapper;

class ItemMapping extends AbstractResourceMapper
{
    public function prepareResource(array $remoteResource) : array
    {
        $job = $this->getJob();
        $resourceName = $job->getResourceName($remoteResource);

        if ('items' !== $resourceName) {
            return $remoteResource;
        }

        if (isset($remoteResource['o-module-mapping:feature'])) {
            // Mapping versions with features use full representations, so
            // there's no need to reconstitute from references.
            return $remoteResource;
        }

        if (isset($remoteResource['o-module-mapping:mapping'])) {
            // Earlier Mapping versions used references instead of full
            // representations, so here we have to get the representations of
            // the remote mapping resource and set it to the local resource.
            try {
                $mapping = $this->getApiOutput($remoteResource['o-module-mapping:mapping']['@id']);
                $remoteResource['o-module-mapping:mapping'] = $mapping;
            } catch (Exception $e) {
                $job->getLogger()->err(sprintf(
                    "Cannot prepare o-module-mapping:mapping data for item: %s\n%s",
                    $remoteResource['@id'],
                    (string) $e,
                ));
            }
        }

        if (isset($remoteResource['o-module-mapping:marker'])) {
            // Earlier Mapping versions used references instead of full
            // representations, so here we have to get the representations of
            // the remote marker resources and set them to the local resource.
            $markers = [];
            foreach ($remoteResource['o-module-mapping:marker'] as $marker) {
                try {
                    $marker = $this->getApiOutput($marker['@id']);
                    $markers[] = $marker;
                } catch (Exception $e) {
                    $job->getLogger()->err(sprintf(
                        "Cannot prepare o-module-mapping:marker data for item: %s\n%s",
                        $remoteResource['@id'],
                        (string) $e,
                    ));
                }
            }
            $remoteResource['o-module-mapping:marker'] = $markers;
        }
        return $remoteResource;
    }

    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $job = $this->getJob();
        $resourceName = $job->getResourceName($remoteResource);

        if ('items' !== $resourceName) {
            return $localResource;
        }

        $entityManager = $job->getEntityManager();

        // Delete all Mapping entities that belong to this local item.
        $dql = 'DELETE Mapping\Entity\Mapping m WHERE m.item = :itemId';
        $query = $entityManager->createQuery($dql);
        $query->setParameter('itemId', $localResource['o:id']);
        $query->execute();

        // Delete all MappingFeature entities that belong to this local item.
        $dql = 'DELETE Mapping\Entity\MappingFeature m WHERE m.item = :itemId';
        $query = $entityManager->createQuery($dql);
        $query->setParameter('itemId', $localResource['o:id']);
        $query->execute();

        // Set the local bounds.
        $remoteBounds = $remoteResource['o-module-mapping:mapping']['o-module-mapping:bounds'] ?? null;
        if ($remoteBounds) {
            $localResource['o-module-mapping:mapping'] = [
                'o-module-mapping:bounds' => $remoteBounds,
            ];
        }

        $mappings = $job->getMappings();

        // Set the local features.
        $remoteFeatures = $remoteResource['o-module-mapping:features'] ?? null;
        if ($remoteFeatures) {
            foreach ($remoteFeatures as $remoteFeature) {
                $localFeature = [
                    'o-module-mapping:geography-type' => $remoteFeature['o-module-mapping:geography-type'],
                    'o-module-mapping:geography-coordinates' => $remoteFeature['o-module-mapping:geography-coordinates'],
                    'o:label' => $remoteFeature['o:label'],
                    'o:media' => null,
                ];
                if (isset($remoteFeature['o:media']['o:id'])) {
                    $localFeature['o:media'] = [
                        'o:id' => $mappings->get('media', $remoteFeature['o:media']['o:id']),
                    ];
                }
                $localResource['o-module-mapping:marker'][] = $localFeature;
            }
        }
        // Map from legacy (pre-2.0) latitude and longitude keys.
        $remoteMarkers = $remoteResource['o-module-mapping:marker'] ?? null;
        if ($remoteMarkers) {
            foreach ($remoteMarkers as $remoteMarker) {
                $localMarker = [
                    'o-module-mapping:geography-type' => 'Point',
                    'o-module-mapping:geography-coordinates' => [$remoteMarker['o-module-mapping:lng'], $remoteMarker['o-module-mapping:lat']],
                    'o:label' => $remoteMarker['o-module-mapping:label'],
                    'o:media' => null,
                ];
                if (isset($remoteMarker['o:media']['o:id'])) {
                    $localMarker['o:media'] = [
                        'o:id' => $mappings->get('media', $remoteMarker['o:media']['o:id']),
                    ];
                }
                $localResource['o-module-mapping:marker'][] = $localMarker;
            }
        }

        return $localResource;
    }

    protected function getApiOutput($url)
    {
        $job = $this->getJob();
        $client = $job->getApiClient($url);
        $query = [
            'key_identity' => $job->getImportEntity()->getKeyIdentity(),
            'key_credential' => $job->getImportEntity()->getKeyCredential(),
        ];
        return $job->getApiOutput($client, $query);
    }
}
