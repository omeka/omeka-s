<?php

namespace EADImport\Job;

use Omeka\Job\AbstractJob;

class ImportJob extends AbstractJob
{
    const MIGRATION_IDENTIFIER = 'EAD';
    protected $propertiesMap;
    protected $itemSetsMap;
    protected $api;
    protected $logger;
    protected $siteId;
    protected $nodesMapping;

    public function perform()
    {
        $services = $this->getServiceLocator();
        $this->api = $services->get('Omeka\ApiManager');
        $this->logger = $services->get('Omeka\Logger');

        $args = $this->job->getArgs();

        $this->setSiteId($args['siteId']);
        $importName = $args['importName'];
        $xmlFile = $args['xmlFilePath'];
        $levelMapping = $args['level_mapping'];
        $nodesMapping = $args['nodes_mapping'];
        $this->setNodesMapping($nodesMapping);
        $authorizedApiAdapters = ['item_sets', 'items'];
        $eadImportJson = [
            'o:job' => ['o:id' => $this->job->getId()],
            'resource_type' => 'items',
            'name' => $importName,
            'mapping' => json_encode($nodesMapping),
            'o:site' => ['o:id' => $this->getSiteId()],
        ];

        $this->api->create('eadimport_imports', $eadImportJson);

        $this->propertiesMap = [];
        $properties = $this->api->search('properties')->getContent();
        foreach ($properties as $property) {
            $this->propertiesMap[$property->id()] = $property->term();
        }

        $migrationItemSet = $this->getItemSetByIdentifier(self::MIGRATION_IDENTIFIER);
        if (!$migrationItemSet) {
            $this->createItemSet(self::MIGRATION_IDENTIFIER, 'EAD Imports');
        }
        foreach ($levelMapping as $nodeName => $apiResourceType) {
            if (in_array($apiResourceType, $authorizedApiAdapters)) {
                $this->logger->info(sprintf('Import %s from level "%s" nodes', $apiResourceType, $nodeName));
                $this->processMapping($apiResourceType, $xmlFile, $nodeName, $nodesMapping[$nodeName]);
            }
        }
    }

    protected function processMapping($apiResourceType, $xmlFile, $nodeName, $nodeMapping)
    {
        $doc = new \DOMDocument();
        $doc->load($xmlFile);

        $hasPartId = array_search('dcterms:hasPart', $this->propertiesMap);
        $isPartOfId = array_search('dcterms:isPartOf', $this->propertiesMap);

        $domxpath = new \DOMXPath($doc);
        $itemSet = $this->getItemSetByIdentifier(self::MIGRATION_IDENTIFIER);

        $parentsAndChilds = [];

        $rootChilds = $domxpath->query('//dsc/*');
        foreach ($rootChilds as $rootChild) {
            if ($rootChild->nodeType === XML_ELEMENT_NODE && $rootChild->nodeName === 'c' && $rootChild->hasAttribute('level')) {
                $rootChild->setAttribute('root_item_set_id', $itemSet->id());
            }
        }

        $itemNodeList = $domxpath->query('//c[@level="' . $nodeName . '"]');

        foreach ($itemNodeList as $itemNode) {
            $itemData = $this->getItemData($doc, $itemNode, $nodeMapping);

            $itemData['o:site'] = ['o:id' => $this->getSiteId()];

            if ($itemSet) {
                $itemData['o:item_set'][] = ['o:id' => $itemSet->id()];
            }

            $resource = $this->api->create($apiResourceType, $itemData)->getContent();
            $this->logger->info('Created resource ' . $resource->id());

            $isItemSetImportDirectChild = $itemNode->hasAttribute('root_item_set_id');
            if ($isItemSetImportDirectChild) {
                $itemSetData['dcterms:hasPart'][] = [
                'property_id' => $hasPartId,
                'type' => 'resource',
                'value_resource_id' => $resource->id(),
            ];
            }

            $appendParentId = $this->appendIdOnChilds($itemNode, $resource->id());
            if ($appendParentId) {
                $parentsAndChilds[$resource->id()] = [];
            }

            $parentIdValues = $domxpath->query('@omeka_parent_id', $itemNode);
            if ($parentIdValues != null) {
                foreach ($parentIdValues as $value) {
                    $parentIdValue = $value->nodeValue;
                    $parentsAndChilds[$parentIdValue][] = $resource->id();
                }
            }
        }

        if ($itemSetData) {
            $this->api->update('item_sets', $itemSet->id(), $itemSetData, [], ['isPartial' => true, 'collectionAction' => 'append']);
        }

        if (! empty($parentsAndChilds)) {
            foreach ($parentsAndChilds as $parentId => $childsIds) {
                if (! empty($parentsAndChilds[$parentId])) {
                    $this->logger->info(sprintf('Childs of %s : %s ', $parentId, implode(";", $childsIds)));

                    $parentData = [];

                    foreach ($childsIds as $childId) {
                        $childData = [];

                        $parentData['dcterms:hasPart'][] = [
                        'property_id' => $hasPartId,
                        'type' => 'resource',
                        'value_resource_id' => $childId,
                    ];

                        $childData['dcterms:isPartOf'][] = [
                        'property_id' => $isPartOfId,
                        'type' => 'resource',
                        'value_resource_id' => $parentId,
                    ];
                        $this->api->update($apiResourceType, $childId, $childData, [], ['isPartial' => true, 'collectionAction' => 'append']);
                    }

                    $this->api->update($apiResourceType, $parentId, $parentData, [], ['isPartial' => true, 'collectionAction' => 'append']);
                }
            }
        }
    }

    protected function getItemData(\DOMDocument $doc, $itemNode, $mapping)
    {
        $itemData = [];

        $domxpath = new \DOMXPath($doc);
        $ancestors = $this->getAncestors($itemNode);

        foreach ($ancestors as $ancestorNode) {
            $ancestorLevel = $ancestorNode->getAttribute('level');
            $nodesMapping = $this->getNodesMapping();

            if ($nodesMapping[$ancestorLevel]) {
                foreach ($nodesMapping[$ancestorLevel] as $ancestorSubnode => $ancestorMapping) {
                    if ($ancestorMapping['make_inherit']) {
                        $targetValues = $domxpath->query($ancestorSubnode, $ancestorNode);

                        if ($targetValues != null) {
                            foreach ($targetValues as $value) {
                                foreach ($ancestorMapping['properties'] as $propertyId) {
                                    $propertyTerm = $this->propertiesMap[$propertyId];

                                    $itemData[$propertyTerm][] = [
                                    'property_id' => $propertyId,
                                    'type' => 'literal',
                                    'is_public' => '1',
                                    '@value' => $value->nodeValue,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        foreach ($mapping as $subnode => $subnodeMapping) {
            $targetValues = $domxpath->query($subnode, $itemNode);

            if ($targetValues != null) {
                foreach ($targetValues as $value) {
                    foreach ($subnodeMapping['properties'] as $propertyId) {
                        $propertyTerm = $this->propertiesMap[$propertyId];

                        $itemData[$propertyTerm][] = [
                            'property_id' => $propertyId,
                            'type' => 'literal',
                            'is_public' => '1',
                            '@value' => $value->nodeValue,
                            ];
                    }
                }
            }
        }

        return $itemData;
    }

    protected function appendIdOnChilds($node, $omekaId)
    {
        $childs = $node->childNodes;
        $hasTargetChild = false;
        foreach ($childs as $child) {
            if ($child->nodeName === 'c') {
                $child->setAttribute('omeka_parent_id', $omekaId);
                $hasTargetChild = true;
            }
        }
        return $hasTargetChild;
    }

    protected function getItemSetByIdentifier($identifier)
    {
        if (isset($this->itemSetsMap) && !array_key_exists($identifier, $this->itemSetsMap)) {
            $data = [
                'property' => [
                    [
                        'property' => 'dcterms:identifier',
                        'text' => $identifier,
                        'type' => 'eq',
                    ],
                ],
            ];
            $itemSets = $this->api->search('item_sets', $data, ['limit' => 1])->getContent();
            $this->itemSetsMap[$identifier] = !empty($itemSets) ? $itemSets[0] : null;
        }

        return $this->itemSetsMap[$identifier];
    }

    protected function createItemSet($identifier, $title)
    {
        $dctermsTitleId = array_search('dcterms:title', $this->propertiesMap);
        $dctermsIdentifierId = array_search('dcterms:identifier', $this->propertiesMap);

        $response = $this->api->create('item_sets', [
            'dcterms:title' => [
                [
                    'property_id' => $dctermsTitleId,
                    'type' => 'literal',
                    'is_public' => '1',
                    '@value' => $title,
                ],
            ],
            'dcterms:identifier' => [
                [
                    'property_id' => $dctermsIdentifierId,
                    'type' => 'literal',
                    'is_public' => '1',
                    '@value' => $identifier,
                ],
            ],
        ]);
        $this->itemSetsMap[$identifier] = $response->getContent();
    }

    protected function getSiteId()
    {
        return $this->siteId;
    }

    protected function setSiteId($siteId)
    {
        $this->siteId = $siteId;

        return $this;
    }

    protected function addParentValue($node, $xpath, $value)
    {
    }

    protected function getAncestors($itemNode)
    {
        $ancestors = [];
        $currentNode = $itemNode;

        while ($currentNode->parentNode !== null) {
            $parentNode = $currentNode->parentNode;
            if ($parentNode->nodeName === 'c') {
                $ancestors[] = $parentNode;
            }
            $currentNode = $parentNode;
        }

        return $ancestors;
    }

    protected function getNodesMapping()
    {
        return $this->nodesMapping;
    }

    protected function setNodesMapping($nodesMapping)
    {
        $this->nodesMapping = $nodesMapping;

        return $this;
    }
}
