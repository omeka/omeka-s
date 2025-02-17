<?php declare(strict_types=1);
/*
 * Copyright Daniel Berthereau, 2018-2024
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Common;

use Common\Stdlib\PsrMessage;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Api\Exception\NotFoundException;
use Omeka\Api\Exception\RuntimeException;
use Omeka\Api\Representation\ResourceTemplateRepresentation;
use Omeka\Entity\Vocabulary;
use Omeka\Module\Exception\ModuleCannotInstallException;

/**
 * Group methods that are useful to install, upgrade or uninstall a module.
 */
class ManageModuleAndResources
{
    /**
     * @var \Omeka\Mvc\Controller\Plugin\Api
     */
    protected $api;

    /**
     * @var \Common\Stdlib\EasyMeta
     */
    protected $easyMeta;

    /**
     * @var \Laminas\ServiceManager\ServiceLocatorInterface
     */
    protected $services;

    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
        // The api plugin allows to search one resource without throwing error.
        $this->api = $services->get('ControllerPluginManager')->get('api');
        $this->easyMeta = $services->get('Common\EasyMeta');
    }

    /**
     * Allows to manage all resources methods that should run once only and that
     * are generic to all modules. A little config over code.
     *
     * @return self
     */
    public function __invoke(): self
    {
        return $this;
    }

    /**
     * Check all resources that are in the path data/ of a module.
     *
     * @throws \Omeka\Module\Exception\ModuleCannotInstallException
     */
    public function checkAllResources(string $module): bool
    {
        $filepathData = OMEKA_PATH . '/modules/' . $module . '/data/';

        // Vocabularies.
        foreach ($this->listFilesInDir($filepathData . 'vocabularies', ['json']) as $filepath) {
            $data = file_get_contents($filepath);
            $data = json_decode($data, true);
            if (!is_array($data)) {
                throw new ModuleCannotInstallException((string) new PsrMessage(
                    'An error occured when loading vocabulary "{vocabulary}": file has no json content.', // @translate
                    ['vocabulary' => pathinfo($filepath, PATHINFO_FILENAME)]
                ));
            }
            $exists = $this->checkVocabulary($data, $module);
            if (is_null($exists)) {
                throw new ModuleCannotInstallException((string) new PsrMessage(
                    'An error occured when adding the prefix "{prefix}": another vocabulary exists. Resolve the conflict before installing this module.', // @translate
                    ['prefix' => $data['vocabulary']['o:prefix']]
                ));
            }
        }

        // Custom vocabs.
        // The presence of the module should be already checked during install.
        foreach ($this->listFilesInDir($filepathData . 'custom-vocabs') as $filepath) {
            $exists = $this->checkCustomVocab($filepath);
            if (is_null($exists)) {
                throw new ModuleCannotInstallException((string) new PsrMessage(
                    'A custom vocab exists for "{name}". Remove it or rename it before installing this module.', // @translate
                    ['name' => pathinfo($filepath, PATHINFO_FILENAME)]
                ));
            }
        }

        // Resource templates.
        foreach ($this->listFilesInDir($filepathData . 'resource-templates') as $filepath) {
            $exists = $this->checkResourceTemplate($filepath);
            if (is_null($exists)) {
                throw new ModuleCannotInstallException((string) new PsrMessage(
                    'A resource template exists for {template}. Rename it or remove it before installing this module.', // @translate
                    ['template' => pathinfo($filepath, PATHINFO_FILENAME)]
                ));
            }
        }

        return true;
    }

    /**
     * Install all resources that are in the path data/ of a module.
     *
     * The data should have been checked first with checkAllResources().
     */
    public function createAllResources(string $module): self
    {
        $filepathData = OMEKA_PATH . '/modules/' . $module . '/data/';

        // Vocabularies.
        foreach ($this->listFilesInDir($filepathData . 'vocabularies', ['json']) as $filepath) {
            $data = file_get_contents($filepath);
            $data = json_decode($data, true);
            if (is_array($data)) {
                $this->createOrUpdateVocabulary($data, $module);
            }
        }

        // Custom vocabs.
        foreach ($this->listFilesInDir($filepathData . 'custom-vocabs') as $filepath) {
            if (!$this->checkCustomVocab($filepath)) {
                $this->createOrUpdateCustomVocab($filepath);
            }
        }

        // Resource templates.
        foreach ($this->listFilesInDir($filepathData . 'resource-templates') as $filepath) {
            if (!$this->checkResourceTemplate($filepath)) {
                $this->createResourceTemplate($filepath);
            }
        }

        return $this;
    }

    /**
     * Check if a vocabulary exists and throws an exception if different.
     *
     * @throws \Omeka\Api\Exception\RuntimeException
     * @return bool False if not found, true if exists, null if a vocabulary
     * exists with the same prefix but a different uri.
     */
    public function checkVocabulary(array $vocabularyData, ?string $module = null): ?bool
    {
        $vocabularyData = $this->prepareVocabularyData($vocabularyData, $module);

        if (!empty($vocabularyData['update']['namespace_uri'])) {
            /** @var \Omeka\Api\Representation\VocabularyRepresentation $vocabularyRepresentation */
            $vocabularyRepresentation = $this->api->searchOne('vocabularies', ['namespace_uri' => $vocabularyData['update']['namespace_uri']])->getContent();
            if ($vocabularyRepresentation) {
                return true;
            }
        }

        $namespaceUri = $vocabularyData['vocabulary']['o:namespace_uri'];
        /** @var \Omeka\Api\Representation\VocabularyRepresentation $vocabularyRepresentation */
        $vocabularyRepresentation = $this->api->searchOne('vocabularies', ['namespace_uri' => $namespaceUri])->getContent();
        if ($vocabularyRepresentation) {
            return true;
        }

        // Check if the vocabulary have been already imported.
        $prefix = $vocabularyData['vocabulary']['o:prefix'];
        $vocabularyRepresentation = $this->api->searchOne('vocabularies', ['prefix' => $prefix])->getContent();
        if (!$vocabularyRepresentation) {
            return false;
        }

        // Check if it is the same vocabulary.
        // See createVocabulary() about the trim.
        if (rtrim($vocabularyRepresentation->namespaceUri(), '#/') === rtrim($namespaceUri, '#/')) {
            return true;
        }

        // It is another vocabulary with the same prefix.
        return null;
    }

    /**
     * Check if a resource template exists.
     *
     * Note: the vocabs of the resource template are not checked currently.
     *
     * @param string $filepath
     * @throws \Omeka\Api\Exception\RuntimeException
     * @return bool False if not found, true if exists.
     */
    public function checkResourceTemplate(string $filepath): bool
    {
        $data = json_decode(file_get_contents($filepath), true);
        if (!$data || empty($data['label'])) {
            return false;
        }

        $template = $this->api->searchOne('resource_templates', ['label' => $data['label']])->getContent();
        return !empty($template);
    }

    /**
     * Check if a custom vocab exists and throws an exception if different.
     *
     * @param string $filepath
     * @throws \Omeka\Api\Exception\RuntimeException
     * @return bool True if the custom vocab exists, false if not or module is
     * not installed, null if exists, but with different metadata (terms,
     * language).
     */
    public function checkCustomVocab(string $filepath): ?bool
    {
        $data = json_decode(file_get_contents($filepath), true);
        if (!$data || empty($data['o:label'])) {
            return false;
        }

        $label = $data['o:label'];
        try {
            // Custom vocab cannot be searched.
            /** @var \CustomVocab\Api\Representation\CustomVocabRepresentation $customVocab */
            $customVocab = $this->api->read('custom_vocabs', ['label' => $label])->getContent();
        } catch (NotFoundException $e) {
            return false;
        } catch (\Omeka\Api\Exception\BadRequestException $e) {
            return false;
        }

        if ($data['o:lang'] != $customVocab->lang()) {
            return null;
        }

        $newItemSet = empty($data['o:item_set']) ? 0 : (int) $data['o:item_set'];
        if ($newItemSet) {
            $existingItemSet = $customVocab->itemSet();
            return $existingItemSet && $newItemSet === $existingItemSet->id() ? true : null;
        }

        $newUris = $data['o:uris'] ?? [];
        if ($newUris) {
            $existingUris = $customVocab->uris();
            asort($newUris);
            asort($existingUris);
            return $newUris === $existingUris ? true : null;
        }

        $newTerms = $data['o:terms'] ?? [];
        $existingTerms = $customVocab->terms();
        // Compatibility with Omeka S v3.
        if (!is_array($existingTerms)) {
            $existingTerms = explode("\n", $existingTerms);
        }
        sort($newTerms);
        sort($existingTerms);
        return $newTerms === $existingTerms ? true : null;
    }

    /**
     * Create or update a vocabulary, with a check of its existence before.
     *
     * The file should have the full path if module is not set.
     *
     * @throws \Omeka\Api\Exception\RuntimeException
     */
    public function createOrUpdateVocabulary(
        array $vocabularyData,
        ?string $module = null
    ): bool {
        $vocabularyData = $this->prepareVocabularyData($vocabularyData, $module);
        $exists = $this->checkVocabulary($vocabularyData, $module);
        if ($exists === false) {
            return $this->createVocabulary($vocabularyData, $module);
        }
        return $this->updateVocabulary($vocabularyData, $module);
    }

    /**
     * Create a vocabulary, with a check of its existence before.
     *
     * @throws \Omeka\Api\Exception\RuntimeException
     * @return bool True if the vocabulary has been created, false if it exists
     * already, so it is not created twice.
     */
    public function createVocabulary(array $vocabularyData, ?string $module = null): bool
    {
        $vocabularyData = $this->prepareVocabularyData($vocabularyData, $module);

        // Check if the vocabulary have been already imported.
        $prefix = $vocabularyData['vocabulary']['o:prefix'];
        /** @var \Omeka\Api\Representation\VocabularyRepresentation $vocabularyRepresentation */
        $vocabularyRepresentation = $this->api->searchOne('vocabularies', ['prefix' => $prefix])->getContent();

        if ($vocabularyRepresentation) {
            // Check if it is the same vocabulary.
            // Note: in some cases, the uri of the ontology and the uri of the
            // namespace are mixed. So, the last character ("#" or "/") is
            // skipped for easier management.
            if (rtrim($vocabularyRepresentation->namespaceUri(), '#/') === rtrim($vocabularyData['vocabulary']['o:namespace_uri'], '#/')) {
                $message = new PsrMessage(
                    'The vocabulary "{vocabulary}" was already installed and was kept.', // @translate
                    ['vocabulary' => $vocabularyData['vocabulary']['o:label']]
                );
                $messenger = $this->services->get('ControllerPluginManager')->get('messenger');
                $messenger->addWarning($message);
                return false;
            }

            // It is another vocabulary with the same prefix.
            throw new RuntimeException((string) new PsrMessage(
                'An error occured when adding the prefix "{prefix}": another vocabulary exists with the same prefix. Resolve the conflict before installing this module.', // @translate
                ['prefix' => $prefix]
            ));
        }

        /** @var \Omeka\Stdlib\RdfImporter $rdfImporter */
        $rdfImporter = $this->services->get('Omeka\RdfImporter');

        try {
            $rdfImporter->import($vocabularyData['strategy'], $vocabularyData['vocabulary'], $vocabularyData['options']);
        } catch (\Omeka\Api\Exception\ValidationException $e) {
            throw new RuntimeException((string) new PsrMessage(
                'An error occured when adding the prefix "{prefix}" and the associated properties: {message}', // @translate
                ['prefix' => $prefix, 'message' => $e->getMessage()]
            ));
        }

        return true;
    }

    public function updateVocabulary(
        array $vocabularyData,
        ?string $module = null
    ): bool {
        $vocabularyData = $this->prepareVocabularyData($vocabularyData, $module);

        $prefix = $vocabularyData['vocabulary']['o:prefix'];
        $namespaceUri = $vocabularyData['vocabulary']['o:namespace_uri'];
        $oldNameSpaceUri = $vocabularyData['update']['namespace_uri'] ?? null;

        if ($oldNameSpaceUri) {
            /** @var \Omeka\Entity\Vocabulary $vocabulary */
            $vocabulary = $this->api->searchOne('vocabularies', ['namespace_uri' => $oldNameSpaceUri], ['responseContent' => 'resource'])->getContent();
        }
        // The old vocabulary may have been already updated.
        if (empty($vocabulary)) {
            $vocabulary = $this->api->searchOne('vocabularies', ['namespace_uri' => $namespaceUri], ['responseContent' => 'resource'])->getContent()
                ?: $this->api->searchOne('vocabularies', ['prefix' => $prefix], ['responseContent' => 'resource'])->getContent();
        }
        if (!$vocabulary) {
            return $this->createVocabulary($vocabularyData, $module);
        }

        // Omeka entities are not fluid.
        $vocabulary->setNamespaceUri($namespaceUri);
        $vocabulary->setPrefix($prefix);
        $vocabulary->setLabel($vocabularyData['vocabulary']['o:label']);
        $vocabulary->setComment($vocabularyData['vocabulary']['o:comment']);

        $entityManager = $this->services->get('Omeka\EntityManager');
        $entityManager->persist($vocabulary);
        $entityManager->flush();

        // Update the names first.
        foreach (['resource_classes', 'properties'] as $name) {
            if (empty($vocabularyData['update'][$name])) {
                continue;
            }
            foreach ($vocabularyData['update'][$name] as $oldLocalName => $newLocalName) {
                if ($oldLocalName === $newLocalName) {
                    continue;
                }
                $old = $this->api->searchOne($name, [
                    'vocabulary_id' => $vocabulary->getId(),
                    'local_name' => $oldLocalName,
                ], ['responseContent' => 'resource'])->getContent();
                if (!$old) {
                    continue;
                }
                $new = $this->api->searchOne($name, [
                    'vocabulary_id' => $vocabulary->getId(),
                    'local_name' => $newLocalName,
                ], ['responseContent' => 'resource'])->getContent();
                if ($new) {
                    $vocabularyData['replace'][$name][$oldLocalName] = $newLocalName;
                    continue;
                }
                $old->setLocalName($newLocalName);
                $entityManager->persist($old);
            }
        }
        $entityManager->flush();

        // Upgrade the classes and the properties.
        /** @var \Omeka\Stdlib\RdfImporter $rdfImporter */
        $rdfImporter = $this->services->get('Omeka\RdfImporter');

        try {
            $diff = $rdfImporter->getDiff($vocabularyData['strategy'], $vocabulary->getNamespaceUri(), $vocabularyData['options']);
        } catch (\Omeka\Api\Exception\ValidationException $e) {
            throw new ModuleCannotInstallException((string) new PsrMessage(
                'An error occured when updating vocabulary "{prefix}" and the associated properties: {message}', // @translate
                ['prefix' => $vocabularyData['vocabulary']['o:prefix'], 'message' => $e->getMessage()]
            ));
        }

        try {
            $diff = $rdfImporter->update($vocabulary->getId(), $diff);
        } catch (\Omeka\Api\Exception\ValidationException $e) {
            throw new ModuleCannotInstallException((string) new PsrMessage(
                'An error occured when updating vocabulary "{prefix}" and the associated properties: {message}', // @translate
                ['prefix' => $vocabularyData['vocabulary']['o:prefix'], 'message' => $e->getMessage()]
            ));
        }

        /** @var \Omeka\Entity\Property[] $properties */
        $owner = $vocabulary->getOwner();
        foreach (['resource_classes', 'properties'] as $name) {
            $members = $this->api->search($name, ['vocabulary_id' => $vocabulary->getId()], ['responseContent' => 'resource'])->getContent();
            foreach ($members as $member) {
                $member->setOwner($owner);
            }
        }

        $entityManager->flush();

        $this->replaceVocabularyMembers($vocabulary, $vocabularyData);

        return true;
    }

    protected function replaceVocabularyMembers(Vocabulary $vocabulary, array $vocabularyData): bool
    {
        $membersByLocalName = [];
        foreach (['resource_classes', 'properties'] as $name) {
            $membersByLocalName[$name] = $this->api->search($name, ['vocabulary_id' => $vocabulary->getId()], ['returnScalar' => 'localName'])->getContent();
            $membersByLocalName[$name] = array_flip($membersByLocalName[$name]);
        }

        // Update names of classes and properties in the case where they where
        // not updated before diff.
        $messenger = $this->services->get('ControllerPluginManager')->get('messenger');
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->services->get('Omeka\Connection');
        $hasReplace = false;
        foreach (['resource_classes', 'properties'] as $name) {
            if (empty($vocabularyData['replace'][$name])) {
                continue;
            }
            // Keep only members with good old local names and good new names.
            $members = array_intersect(
                array_intersect_key($vocabularyData['replace'][$name], $membersByLocalName[$name]),
                array_flip($membersByLocalName[$name])
            );
            if (empty($members)) {
                continue;
            }
            foreach ($members as $oldLocalName => $newLocalName) {
                if ($oldLocalName === $newLocalName
                    || empty($membersByLocalName[$name][$oldLocalName])
                    || empty($membersByLocalName[$name][$newLocalName])
                ) {
                    continue;
                }
                $oldMemberId = $membersByLocalName[$name][$oldLocalName];
                $newMemberId = $membersByLocalName[$name][$newLocalName];
                // Update all places that uses the old name with new name, then
                // remove the old member.
                if ($name === 'resource_classes') {
                    $sqls = <<<SQL
UPDATE `resource`
SET `resource_class_id` = $newMemberId
WHERE `resource_class_id` = $oldMemberId;

UPDATE `resource_template`
SET `resource_class_id` = $newMemberId
WHERE `resource_class_id` = $oldMemberId;

DELETE FROM `resource_class`
WHERE `id` = $oldMemberId;

SQL;
                } else {
                    $sqls = <<<SQL
UPDATE `value`
SET `property_id` = $newMemberId
WHERE `property_id` = $oldMemberId;

UPDATE `resource_template_property`
SET `property_id` = $newMemberId
WHERE `property_id` = $oldMemberId;

DELETE FROM `property`
WHERE `id` = $oldMemberId;

SQL;
                }
                foreach (array_filter(explode(";\n", $sqls)) as $sql) {
                    $connection->executeStatement($sql);
                }
            }
            $hasReplace = true;
            // TODO Ideally, anywhere this option is used in the setting should be updated too.
            $message = new PsrMessage(
                'The following "{name}" of the vocabulary "{label}" were replaced: {members}', // @translate
                ['name' => $name, 'label' => $vocabularyData['vocabulary']['o:label'], 'members' => json_encode($members, 448)]
            );
            $messenger->addWarning($message);
        }
        if ($hasReplace) {
            $entityManager = $this->services->get('Omeka\EntityManager');
            $entityManager->flush();

            $message = new PsrMessage(
                'Resources, values and templates were updated, but you may check settings where the old ones were used.' // @translate
            );
            $messenger->addWarning($message);
        }

        return true;
    }

    /**
     * Create a resource template, with a check of its existence before.
     *
     * @todo Some checks of the resource template controller are skipped currently.
     *
     * @param string $filepath
     * @throws \Omeka\Api\Exception\RuntimeException
     * @return \Omeka\Api\Representation\ResourceTemplateRepresentation
     */
    public function createResourceTemplate(string $filepath): ResourceTemplateRepresentation
    {
        $data = json_decode(file_get_contents($filepath), true);

        // Check if the resource template exists, so it is not replaced.
        $label = $data['o:label'] ?? '';

        $resourceTemplate = $this->api->searchOne('resource_templates', ['label' => $label])->getContent();
        if ($resourceTemplate) {
            $message = new PsrMessage(
                'The resource template named "{template}" is already available and is skipped.', // @translate
                ['template' => $label]
            );
            $messenger = $this->services->get('ControllerPluginManager')->get('messenger');
            $messenger->addWarning($message);
            return $resourceTemplate;
        }

        // The check sets the internal ids of classes, properties and data types
        // and converts old data types into multiple data types and prepare
        // other data types (mainly custom vocabs).
        $data = $this->flagValid($data);

        // Process import.
        return $this->api->create('resource_templates', $data)->getContent();
    }

    /**
     * Create or update a custom vocab.
     *
     * @param string $filepath
     * @return ?\CustomVocab\Api\Representation\CustomVocabRepresentation|null
     */
    public function createOrUpdateCustomVocab(string $filepath): ?\CustomVocab\Api\Representation\CustomVocabRepresentation
    {
        try {
            return $this->updateCustomVocab($filepath);
        } catch (RuntimeException $e) {
            return $this->createCustomVocab($filepath);
        }
    }

    /**
     * Create a custom vocab.
     *
     * @param string $filepath
     * @return \CustomVocab\Api\Representation\CustomVocabRepresentation|null
     */
    public function createCustomVocab(string $filepath): ?\CustomVocab\Api\Representation\CustomVocabRepresentation
    {
        $data = json_decode(file_get_contents($filepath), true);
        if (!$data) {
            return null;
        }
        $data['o:terms'] = implode(PHP_EOL, $data['o:terms'] ?? []);
        try {
            return $this->api->create('custom_vocabs', $data)->getContent();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Flag members and data types as valid.
     *
     * Copy of the method of the resource template controller (with services)
     * and remove of keys "data_types" inside "o:data".
     *
     * @see \AdvancedResourceTemplate\Controller\Admin\ResourceTemplateControllerDelegator::flagValid()
     *
     * All members start as invalid until we determine whether the corresponding
     * vocabulary and member exists in this installation. All data types start
     * as "Default" (i.e. none declared) until we determine whether they match
     * the native types (literal, uri, resource).
     *
     * We flag a valid vocabulary by adding [vocabulary_prefix] to the member; a
     * valid class by adding [o:id]; and a valid property by adding
     * [o:property][o:id]. We flag a valid data type by adding [o:data_type] to
     * the property. By design, the API will only hydrate members and data types
     * that are flagged as valid.
     *
     * @todo Manage direct import of data types from Value Suggest and other modules.
     *
     * @param array $import
     * @return array|false
     */
    protected function flagValid(iterable $import)
    {
        $getDataTypesByName = function ($dataTypesNameLabels) {
            $result = [];
            foreach ($dataTypesNameLabels ?? [] as $index => $dataTypeNameLabel) {
                // Manage associative array name / label, even if it should not exist.
                if (is_string($dataTypeNameLabel)) {
                    if (is_numeric($index)) {
                        $result[$dataTypeNameLabel] = is_numeric($index)
                            ? ['name' => $dataTypeNameLabel, 'label' => $dataTypeNameLabel]
                            : ['name' => $index, 'label' => $dataTypeNameLabel];
                    } else {
                        // TODO Finalize here?
                    }
                } else {
                    $result[$dataTypeNameLabel['name']] = $dataTypeNameLabel;
                }
            }
            return $result;
        };

        if (isset($import['o:resource_class'])) {
            $vocabPrefix = $this->easyMeta->vocabularyPrefix($import['o:resource_class']['vocabulary_namespace_uri']);
            if ($vocabPrefix) {
                $import['o:resource_class']['vocabulary_prefix'] = $vocabPrefix;
                $import['o:resource_class']['o:id'] = $this->easyMeta->resourceClassId($vocabPrefix . ':' . $import['o:resource_class']['local_name']);
            }
        }

        foreach (['o:title_property', 'o:description_property'] as $property) {
            if (isset($import[$property])) {
                $vocabPrefix = $this->easyMeta->vocabularyPrefix($import[$property]['vocabulary_namespace_uri']);
                if ($vocabPrefix) {
                    $import[$property]['vocabulary_prefix'] = $vocabPrefix;
                    $import[$property]['o:id'] = $this->easyMeta->propertyId($vocabPrefix . ':' . $import[$property]['local_name']);
                }
            }
        }

        foreach ($import['o:resource_template_property'] as $key => $property) {
            $vocabPrefix = $this->easyMeta->vocabularyPrefix($property['vocabulary_namespace_uri']);
            if ($vocabPrefix) {
                $import['o:resource_template_property'][$key]['vocabulary_prefix'] = $vocabPrefix;
                $propertyId = $this->easyMeta->propertyId($vocabPrefix . ':' . $property['local_name']);
                if ($propertyId) {
                    $import['o:resource_template_property'][$key]['o:property'] = ['o:id' => $propertyId];
                    // Check the deprecated "data_type_name" if needed and
                    // normalize it.
                    if (!array_key_exists('data_types', $import['o:resource_template_property'][$key])) {
                        if (!empty($import['o:resource_template_property'][$key]['data_type_name'])
                            && !empty($import['o:resource_template_property'][$key]['data_type_label'])
                        ) {
                            $import['o:resource_template_property'][$key]['data_types'] = [[
                                'name' => $import['o:resource_template_property'][$key]['data_type_name'],
                                'label' => $import['o:resource_template_property'][$key]['data_type_label'],
                            ]];
                        } else {
                            $import['o:resource_template_property'][$key]['data_types'] = [];
                        }
                    }
                    unset($import['o:resource_template_property'][$key]['data_type_name']);
                    unset($import['o:resource_template_property'][$key]['data_type_label']);
                    $import['o:resource_template_property'][$key]['data_types'] = $getDataTypesByName($import['o:resource_template_property'][$key]['data_types']);
                    // Prepare the list of standard data types.
                    $import['o:resource_template_property'][$key]['o:data_type'] = [];
                    foreach (array_keys($import['o:resource_template_property'][$key]['data_types']) as $name) {
                        $known = $this->easyMeta->dataTypeName($name);
                        if ($known) {
                            $import['o:resource_template_property'][$key]['o:data_type'][] = $known;
                            $import['o:resource_template_property'][$key]['data_types'][$name]['name'] = $known;
                        }
                    }
                    $import['o:resource_template_property'][$key]['o:data_type'] = array_unique($import['o:resource_template_property'][$key]['o:data_type']);
                    // Prepare the list of standard data types for duplicated
                    // properties (only one most of the time, that is the main).
                    $import['o:resource_template_property'][$key]['o:data'] = array_values($import['o:resource_template_property'][$key]['o:data'] ?? []);
                    $import['o:resource_template_property'][$key]['o:data'][0]['data_types'] = $import['o:resource_template_property'][$key]['data_types'] ?? [];
                    $import['o:resource_template_property'][$key]['o:data'][0]['o:data_type'] = $import['o:resource_template_property'][$key]['o:data_type'] ?? [];
                    $first = true;
                    foreach ($import['o:resource_template_property'][$key]['o:data'] as $k => $rtpData) {
                        if ($first) {
                            $first = false;
                            // Specific to the installer.
                            unset($import['o:resource_template_property'][$key]['o:data'][$k]['data_types']);
                            continue;
                        }
                        // Prepare the list of standard data types if any.
                        $import['o:resource_template_property'][$key]['o:data'][$k]['o:data_type'] = [];
                        if (empty($rtpData['data_types'])) {
                            continue;
                        }
                        $import['o:resource_template_property'][$key]['o:data'][$k]['data_types'] = $getDataTypesByName($import['o:resource_template_property'][$key]['o:data'][$k]['data_types']);
                        foreach (array_keys($import['o:resource_template_property'][$key]['o:data'][$k]['data_types']) as $name) {
                            $known = $this->easyMeta->dataTypeName($name);
                            if ($known) {
                                $import['o:resource_template_property'][$key]['o:data'][$k]['o:data_type'][] = $known;
                                $import['o:resource_template_property'][$key]['o:data'][$k]['data_types'][$name]['name'] = $known;
                            }
                        }
                        $import['o:resource_template_property'][$key]['o:data'][$k]['o:data_type'] = array_unique($import['o:resource_template_property'][$key]['o:data'][$k]['o:data_type']);
                        // Specific to the installer.
                        unset($import['o:resource_template_property'][$key]['o:data'][$k]['data_types']);
                    }
                }
            }
        }

        return $import;
    }

    /**
     * Update a vocabulary, with a check of its existence before.
     *
     * @param string $filepath
     * @throws \Omeka\Api\Exception\RuntimeException
     * @return \CustomVocab\Api\Representation\CustomVocabRepresentation
     */
    public function updateCustomVocab(string $filepath): \CustomVocab\Api\Representation\CustomVocabRepresentation
    {
        $data = json_decode(file_get_contents($filepath), true);

        $label = $data['o:label'];
        try {
            /** @var \CustomVocab\Api\Representation\CustomVocabRepresentation $customVocab */
            $customVocab = $this->api->read('custom_vocabs', ['label' => $label])->getContent();
        } catch (NotFoundException $e) {
            throw new RuntimeException(
                (string) new PsrMessage(
                    'The custom vocab named "{custom_vocab}" is not available.', // @translate
                    ['custom_vocab' => $label]
                )
            );
        }

        $newItemSet = empty($data['o:item_set']) ? 0 : (int) $data['o:item_set'];
        $newUris = $data['o:uris'] ?? [];
        $newTerms = $data['o:terms'] ?? [];

        $isV4 = version_compare(\Omeka\Module::VERSION, '4', '>=');
        if (!$isV4) {
            if ($newItemSet) {
                $this->api->update('custom_vocabs', $customVocab->id(), [
                    'o:label' => $label,
                    'o:item_set' => $newItemSet,
                    'o:terms' => '',
                    'o:uris' => '',
                ], [], ['isPartial' => true]);
            } elseif ($newUris) {
                $existingUris = $customVocab->uris();
                if (!is_array($existingUris)) {
                    $nu = [];
                    foreach (explode("\n", $existingUris) as $existingUri) {
                        [$uri, $uriLabel] = array_map('trim', explode("=", $existingUri, 2));
                        $nu[$uri] = $uriLabel ?: $uri;
                    }
                    $existingUris = $nu;
                }
                if (!is_array($newUris)) {
                    $nu = [];
                    foreach (array_filter(explode("\n", $newUris)) as $newUri) {
                        [$uri, $uriLabel] = array_map('trim', explode("=", $newUri, 2));
                        $nu[$uri] = $uriLabel ?: $uri;
                    }
                    $newUris = $nu;
                }
                $newUris = array_replace($newUris, $existingUris, $newUris);
                $newUrisString = '';
                foreach ($newUris as $uri => $uriLabel) {
                    $newUrisString .= $uri . ' = ' . $uriLabel . "\n";
                }
                $newUrisString = trim($newUrisString);
                $this->api->update('custom_vocabs', $customVocab->id(), [
                    'o:label' => $label,
                    'o:item_set' => null,
                    'o:terms' => '',
                    'o:uris' => $newUrisString,
                ], [], ['isPartial' => true]);
            } elseif ($newTerms) {
                $existingTerms = $customVocab->terms();
                if (!is_array($existingTerms)) {
                    $existingTerms = explode("\n", $existingTerms);
                }
                if (!is_array($newTerms)) {
                    $newTerms = explode("\n", $newTerms);
                }
                $termsToStore = array_values(array_merge($existingTerms, $newTerms));
                $this->api->update('custom_vocabs', $customVocab->id(), [
                    'o:label' => $label,
                    'o:item_set' => null,
                    'o:terms' => implode("\n", $termsToStore),
                    'o:uris' => '',
                ], [], ['isPartial' => true]);
            }
            return $customVocab;
        }

        if ($newItemSet) {
            $this->api->update('custom_vocabs', $customVocab->id(), [
                'o:label' => $label,
                'o:item_set' => $newItemSet,
                'o:terms' => [],
                'o:uris' => [],
            ], [], ['isPartial' => true]);
        } elseif ($newUris) {
            $this->api->update('custom_vocabs', $customVocab->id(), [
                'o:label' => $label,
                'o:item_set' => null,
                'o:terms' => [],
                'o:uris' => array_replace($newUris, $customVocab->uris(), $newUris),
            ], [], ['isPartial' => true]);
        } elseif ($newTerms) {
            $this->api->update('custom_vocabs', $customVocab->id(), [
                'o:label' => $label,
                'o:item_set' => null,
                'o:terms' => array_values(array_merge($customVocab->terms(), $newTerms)),
                'o:uris' => [],
            ], [], ['isPartial' => true]);
        }

        return $customVocab;
    }

    /**
     * Remove a vocabulary by its prefix.
     *
     * @param string $prefix
     * @return self
     */
    public function removeVocabulary(string $prefix): self
    {
        // The vocabulary may have been removed manually before.
        $resource = $this->api->searchOne('vocabularies', ['prefix' => $prefix])->getContent();
        if ($resource) {
            try {
                $this->api->delete('vocabularies', $resource->id());
            } catch (\Exception $e) {
            }
        }
        return $this;
    }

    /**
     * Remove a resource template by its label.
     *
     * @param string $label
     * @return self
     */
    public function removeResourceTemplate(string $label): self
    {
        // The resource template may be renamed or removed manually before.
        try {
            $resource = $this->api->read('resource_templates', ['label' => $label])->getContent();
            $this->api->delete('resource_templates', $resource->id());
        } catch (\Exception $e) {
        }
        return $this;
    }

    /**
     * Remove a custom vocab by its label.
     *
     * @param string $label
     * @return self
     */
    public function removeCustomVocab(string $label): self
    {
        // The custom vocab may be renamed or removed manually before.
        try {
            $resource = $this->api->read('custom_vocabs', ['label' => $label])->getContent();
            $this->api->delete('custom_vocabs', $resource->id());
        } catch (NotFoundException $e) {
        }
        return $this;
    }

    /**
     * Get the full file path inside the data directory. The path may be an url.
     */
    public function fileDataPath(string $fileOrUrl, ?string $module = null, ?string $dataDirectory = null): ?string
    {
        if (!$fileOrUrl) {
            return null;
        }

        $fileOrUrl = trim($fileOrUrl);
        if (strpos($fileOrUrl, 'https://') !== false || strpos($fileOrUrl, 'http://') !== false) {
            return $fileOrUrl;
        }

        // Check if this is already the full path.
        $modulesPath = OMEKA_PATH . '/modules/';
        if (strpos($fileOrUrl, $modulesPath) === 0) {
            $filepath = $fileOrUrl;
        } elseif (!$module) {
            return null;
        } else {
            $filepath = $modulesPath . $module . '/data/' . ($dataDirectory ? $dataDirectory . '/' : '') . $fileOrUrl;
        }

        if (file_exists($filepath) && filesize($filepath) && is_readable($filepath)) {
            return $filepath;
        }

        return null;
    }

    protected function prepareVocabularyData(array $vocabularyData, ?string $module = null): array
    {
        if (!empty($vocabularyData['is_checked'])) {
            return $vocabularyData;
        }

        $filepath = $vocabularyData['options']['file'] ?? $vocabularyData['options']['url']
            ?? $vocabularyData['file'] ?? $vocabularyData['url'] ?? null;
        if (!$filepath) {
            throw new RuntimeException((string) new PsrMessage(
                'No file or url set for the vocabulary.' // @translate
            ));
        }

        $filepath = trim($filepath);
        $isUrl = strpos($filepath, 'http:/') === 0 || strpos($filepath, 'https:/') === 0;
        if ($isUrl) {
            $fileContent = file_get_contents($filepath);
            if (empty($fileContent)) {
                throw new RuntimeException((string) new PsrMessage(
                    'The file "{file}" cannot be read. Check the url.', // @translate
                    ['file' => strpos($filepath, '/') === 0 ? basename($filepath) : $filepath]
                ));
            }
            $vocabularyData['strategy'] = 'url';
            $vocabularyData['options']['url'] = $filepath;
        } else {
            $filepath = $this->fileDataPath($filepath, $module, 'vocabularies');
            if (!$filepath) {
                throw new RuntimeException((string) new PsrMessage(
                    'The file "{file}" cannot be read. Check the file.', // @translate
                    ['file' => strpos($filepath, '/') === 0 ? basename($filepath) : $filepath]
                ));
            }
            $vocabularyData['strategy'] = 'file';
            $vocabularyData['options']['file'] = $filepath;
        }

        if (isset($vocabularyData['format'])) {
            $vocabularyData['options']['format'] = $vocabularyData['format'];
        }
        unset(
            $vocabularyData['file'],
            $vocabularyData['url'],
            $vocabularyData['format']
        );

        $namespaceUri = $vocabularyData['vocabulary']['o:namespace_uri'] ?? '';
        $prefix = $vocabularyData['vocabulary']['o:prefix'] ?? '';
        if (!$namespaceUri || !$prefix) {
            throw new RuntimeException((string) new PsrMessage(
                'A vocabulary must have a namespace uri and a prefix.' // @translate
            ));
        }

        $vocabularyData['is_checked'] = true;
        return $vocabularyData;
    }

    /**
     * List filtered files in a directory, not recursively, and without subdirs.
     *
     * Unreadable and empty files are skipped.
     *
     * @param string $dirpath
     * @param array $extensions
     * @return array
     */
    protected function listFilesInDir($dirpath, iterable $extensions = []): array
    {
        if (empty($dirpath) || !file_exists($dirpath) || !is_dir($dirpath) || !is_readable($dirpath)) {
            return [];
        }
        $list = array_filter(
            array_map(fn ($file) => $dirpath . DIRECTORY_SEPARATOR . $file, scandir($dirpath)),
            fn ($file) => is_file($file) && is_readable($file) && filesize($file)
        );
        if ($extensions) {
            $list = array_filter($list, fn ($file) => in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions));
        }
        return array_values($list);
    }

    /**
     * Check for a list of strings in files to handle upgrade of modules.
     *
     * @param array|string $stringsOrRegex If an array is passed, each string is
     *   checked via method mb_strpos(); else it is a full regex for preg_match.
     * @param string $globPath The glob path to get files to check, for example
     *   "themes/* /view/search/*".
     * @param bool $invert Get files without the string.
     * @return array Results by key, even for regex. Return null if issue.
     */
    public function checkStringsInFiles($stringsOrRegex, string $globPath = '', bool $invert = false): ?array
    {
        if (!$stringsOrRegex) {
            return [];
        }

        // Forbid fake paths.
        if (strpos($globPath, '..') !== false || strpos($globPath, './') !== false) {
            return null;
        }

        $start = mb_strlen(OMEKA_PATH . '/');
        if (mb_substr($globPath, 0, 1) === '/') {
            if (strpos($globPath, $start) !== 0) {
                return null;
            }
        } else {
            $globPath = OMEKA_PATH . '/' . $globPath;
        }

        $result = [];

        $isStrings = is_array($stringsOrRegex);

        $paths = glob($globPath, GLOB_BRACE);
        foreach ($paths as $filepath) {
            if (!is_file($filepath) || !is_readable($filepath) || !filesize($filepath)) {
                continue;
            }
            $phtml = file_get_contents($filepath);
            if ($isStrings) {
                foreach ($stringsOrRegex as $check) {
                    $pos = mb_strpos($phtml, $check);
                    if ((!$invert && $pos) || ($invert && !$pos)) {
                        $result[] = mb_substr($filepath, $start);
                    }
                }
            } else {
                $has = preg_match($phtml, $stringsOrRegex);
                if ((!$invert && $has) || ($invert && !$has)) {
                    $result[] = mb_substr($filepath, $start);
                }
            }
        }

        return $result;
    }

    /**
     * Clear php caches.
     *
     * This method may be used when updating the schema of an entity, in which
     * case the cache may need to be refreshed.
     */
    public function clearCaches(): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        if (function_exists('apcu_clear_cache')) {
            apcu_clear_cache();
        }
        @clearstatcache(true);
    }
}
