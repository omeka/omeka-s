<?php
namespace NumericDataTypes;

use Composer\Semver\Comparator;
use Doctrine\Common\Collections\Criteria;
use NumericDataTypes\Form\Element\ConvertToNumeric;
use Omeka\Module\AbstractModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function install(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('CREATE TABLE numeric_data_types_duration (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_E1B5FC6089329D25 (resource_id), INDEX IDX_E1B5FC60549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE numeric_data_types_integer (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_6D39C79089329D25 (resource_id), INDEX IDX_6D39C790549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE numeric_data_types_timestamp (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_7367AFAA89329D25 (resource_id), INDEX IDX_7367AFAA549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE numeric_data_types_interval (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, value2 BIGINT NOT NULL, INDEX IDX_7E2C936B89329D25 (resource_id), INDEX IDX_7E2C936B549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE numeric_data_types_duration ADD CONSTRAINT FK_E1B5FC6089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_duration ADD CONSTRAINT FK_E1B5FC60549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_integer ADD CONSTRAINT FK_6D39C79089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_integer ADD CONSTRAINT FK_6D39C790549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_timestamp ADD CONSTRAINT FK_7367AFAA89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_timestamp ADD CONSTRAINT FK_7367AFAA549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_interval ADD CONSTRAINT FK_7E2C936B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE numeric_data_types_interval ADD CONSTRAINT FK_7E2C936B549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('DROP TABLE IF EXISTS numeric_data_types_duration;');
        $conn->exec('DROP TABLE IF EXISTS numeric_data_types_integer;');
        $conn->exec('DROP TABLE IF EXISTS numeric_data_types_timestamp;');
        $conn->exec('DROP TABLE IF EXISTS numeric_data_types_interval;');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        if (Comparator::lessThan($oldVersion, '1.1.0-alpha')) {
            $conn->exec('CREATE TABLE numeric_data_types_interval (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, value2 BIGINT NOT NULL, INDEX IDX_7E2C936B89329D25 (resource_id), INDEX IDX_7E2C936B549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
            $conn->exec('ALTER TABLE numeric_data_types_interval ADD CONSTRAINT FK_7E2C936B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;');
            $conn->exec('ALTER TABLE numeric_data_types_interval ADD CONSTRAINT FK_7E2C936B549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
        }
        if (Comparator::lessThan($oldVersion, '1.2.0')) {
            // The numeric_data_types_duration table was mistakenly not created
            // in the previous upgrade. Create it now.
            $conn->exec('CREATE TABLE numeric_data_types_duration (id INT AUTO_INCREMENT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value BIGINT NOT NULL, INDEX IDX_E1B5FC6089329D25 (resource_id), INDEX IDX_E1B5FC60549213EC (property_id), INDEX property_value (property_id, value), INDEX value (value), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;');
            $conn->exec('ALTER TABLE numeric_data_types_duration ADD CONSTRAINT FK_E1B5FC6089329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE;');
            $conn->exec('ALTER TABLE numeric_data_types_duration ADD CONSTRAINT FK_E1B5FC60549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'buildQueries']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.search.query',
            [$this, 'sortQueries']
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'convertToNumeric'],
            100 // Set a high priority so this runs before saveNumericData().
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.hydrate.post',
            [$this, 'saveNumericData']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.sort-selector',
            [$this, 'addSortings']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.sort-selector',
            [$this, 'addSortings']
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'common/numeric-data-types-advanced-search';
                $event->setParam('partials', $partials);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.advanced_search',
            function (Event $event) {
                $partials = $event->getParam('partials');
                $partials[] = 'common/numeric-data-types-advanced-search';
                $event->setParam('partials', $partials);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Form\ResourceBatchUpdateForm',
            'form.add_elements',
            function (Event $event) {
                $form = $event->getTarget();
                $form->add([
                    'type' => ConvertToNumeric::class,
                    'name' => 'numeric_convert',
                ]);
            }
        );
        $sharedEventManager->attach(
            'Omeka\Api\Adapter\ItemAdapter',
            'api.preprocess_batch_update',
            function (Event $event) {
                $data = $event->getParam('data');
                $rawData = $event->getParam('request')->getContent();
                if ($this->convertToNumericDataIsValid($rawData)) {
                    $data['numeric_convert'] = $rawData['numeric_convert'];
                }
                $event->setParam('data', $data);
            }
        );
    }

    /**
     * Convert property values to the specified numeric data type.
     *
     * This will work for Item, ItemSet, and Media resources.
     *
     * @param Event $event
     */
    public function convertToNumeric(Event $event)
    {
        $entity = $event->getParam('entity');
        if ($entity instanceof \Omeka\Entity\Item) {
            $resource = 'items';
        } elseif ($entity instanceof \Omeka\Entity\ItemSet) {
            $resource = 'item_sets';
        } elseif ($entity instanceof \Omeka\Entity\Media) {
            $resource = 'media';
        } else {
            return; // This is not a resource entity.
        }

        $data = $event->getParam('request')->getContent();
        if (!$this->convertToNumericDataIsValid($data)) {
            return; // This is not a convert-to-numeric request.
        }

        $propertyId = (int) $data['numeric_convert']['property'];
        $type = $data['numeric_convert']['type'];

        $services = $this->getServiceLocator();
        $entityManager = $services->get('Omeka\EntityManager');
        $dataType = $services->get('Omeka\DataTypeManager')->get($type);
        $adapter = $services->get('Omeka\ApiAdapterManager')->get($resource);
        $logger = $services->get('Omeka\Logger');

        // Get the property entity.
        $dql = 'SELECT p FROM Omeka\Entity\Property p WHERE p.id = :id';
        $property = $entityManager->createQuery($dql)
            ->setParameter('id', $propertyId)
            ->getOneOrNullResult();
        if (null === $property) {
            return; // The property doesn't exist. Do nothing.
        }

        // Only convert literal values of the specified property.
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('property', $property))
            ->andWhere(Criteria::expr()->eq('type', 'literal'));
        $values = $entity->getValues()->matching($criteria);
        foreach ($values as $value) {
            $valueObject = ['@value' => $value->getValue()];
            if ($dataType->isValid($valueObject)) {
                $value->setType($type);
                $dataType->hydrate($valueObject, $value, $adapter);
            } else {
                $message = sprintf(
                    'NumericDataTypes - invalid %s value for ID %s - %s', // @translate
                    $type, $entity->getId(), $value->getValue()
                );
                $logger->notice($message);
            }
        }
    }

    /**
     * Save numeric data to the corresponding number tables.
     *
     * This clears all existing numbers and (re)saves them during create and
     * update operations for a resource (item, item set, media). We do this as
     * an easy way to ensure that the numbers in the number tables are in sync
     * with the numbers in the value table.
     *
     * This will work for Item, ItemSet, and Media resources.
     *
     * @param Event $event
     */
    public function saveNumericData(Event $event)
    {
        $entity = $event->getParam('entity');
        if (!$entity instanceof \Omeka\Entity\Resource) {
            return; // This is not a resource entity.
        }

        $allValues = $entity->getValues();
        foreach ($this->getNumericDataTypes() as $dataTypeName => $dataType) {
            $criteria = Criteria::create()
                ->where(Criteria::expr()
                ->eq('type', $dataTypeName));
            $matchingValues = $allValues->matching($criteria);
            if (!$matchingValues) {
                // This resource has no number values of this type.
                continue;
            }

            $em = $this->getServiceLocator()->get('Omeka\EntityManager');
            $existingNumbers = [];

            if ($entity->getId()) {
                $dql = sprintf(
                    'SELECT n FROM %s n WHERE n.resource = :resource',
                    $dataType->getEntityClass()
                );
                $query = $em->createQuery($dql);
                $query->setParameter('resource', $entity);
                $existingNumbers = $query->getResult();
            }
            foreach ($matchingValues as $value) {
                // Avoid ID churn by reusing number rows.
                $number = current($existingNumbers);
                if ($number === false) {
                    // No more number rows to reuse. Create a new one.
                    $entityClass = $dataType->getEntityClass();
                    $number = new $entityClass;
                    $em->persist($number);
                } else {
                    // Null out numbers as we reuse them. Note that existing
                    // numbers are already managed and will update during flush.
                    $existingNumbers[key($existingNumbers)] = null;
                    next($existingNumbers);
                }
                $number->setResource($entity);
                $number->setProperty($value->getProperty());
                $dataType->setEntityValues($number, $value);
            }
            // Remove any numbers that weren't reused.
            foreach ($existingNumbers as $existingNumber) {
                if (null !== $existingNumber) {
                    $em->remove($existingNumber);
                }
            }
        }
    }

    /**
     * Build numerical queries.
     *
     * @param Event $event
     */
    public function buildQueries(Event $event)
    {
        $query = $event->getParam('request')->getContent();
        if (!isset($query['numeric'])) {
            return;
        }
        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        foreach ($this->getNumericDataTypes() as $dataType) {
            $dataType->buildQuery($adapter, $qb, $query);
        }
    }

    /**
     * Sort numerical queries.
     *
     * sort_by=numeric:<type>:<propertyId>
     *
     * @param Event $event
     */
    public function sortQueries(Event $event)
    {
        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');
        $query = $event->getParam('request')->getContent();

        if (!isset($query['sort_by']) || !is_string($query['sort_by'])) {
            return;
        }
        $sortBy = explode(':', $query['sort_by']);
        if (3 !== count($sortBy)) {
            return;
        }
        [$namespace, $type, $propertyId] = $sortBy;
        if ('numeric' !== $namespace || !is_string($type) || !is_numeric($propertyId)) {
            return;
        }
        foreach ($this->getNumericDataTypes() as $dataType) {
            $dataType->sortQuery($adapter, $qb, $query, $type, $propertyId);
        }
    }

    /**
     * Add numeric sort options to sort by form.
     *
     * @param Event $event
     */
    public function addSortings(Event $event)
    {
        $services = $this->getServiceLocator();
        $translator = $services->get('MvcTranslator');
        $entityManager = $services->get('Omeka\EntityManager');

        $qb = $entityManager->createQueryBuilder();
        $qb->select(['p.id', 'p.label', 'rtp.dataType'])
            ->from('Omeka\Entity\ResourceTemplateProperty', 'rtp')
            ->innerJoin('rtp.property', 'p');
        $qb->andWhere($qb->expr()->isNotNull('rtp.dataType'));
        $query = $qb->getQuery();

        $numericDataTypes = $this->getNumericDataTypes();
        $numericSortBy = [];
        foreach ($query->getResult() as $templatePropertyData) {
            $dataTypes = $templatePropertyData['dataType'] ?? [];
            foreach ($dataTypes as $dataType) {
                if (isset($numericDataTypes[$dataType])) {
                    $value = sprintf('%s:%s', $dataType, $templatePropertyData['id']);
                    if (!isset($numericSortBy[$value])) {
                        $numericSortBy[$value] = sprintf('%s (%s)', $translator->translate($templatePropertyData['label']), $dataType);
                    }
                }
            }
        }
        // Sort options alphabetically.
        asort($numericSortBy);
        $sortConfig = $event->getParam('sortConfig') ?: [];
        $sortConfig = array_merge($sortConfig, $numericSortBy);
        $event->setParam('sortConfig', $sortConfig);
    }

    /**
     * Get all data types added by this module.
     *
     * @return array
     */
    public function getNumericDataTypes()
    {
        $dataTypes = $this->getServiceLocator()->get('Omeka\DataTypeManager');
        $numericDataTypes = [];
        foreach ($dataTypes->getRegisteredNames() as $dataType) {
            if (0 === strpos($dataType, 'numeric:')) {
                $numericDataTypes[$dataType] = $dataTypes->get($dataType);
            }
        }
        return $numericDataTypes;
    }

    /**
     * Does the passed data contain valid convert-to-numeric data?
     *
     * @param array $data
     * return bool
     */
    public function convertToNumericDataIsValid(array $data)
    {
        $validTypes = array_keys($this->getNumericDataTypes());
        return (
            isset($data['numeric_convert']['property'])
            && is_numeric($data['numeric_convert']['property'])
            && isset($data['numeric_convert']['type'])
            && in_array($data['numeric_convert']['type'], $validTypes)
        );
    }
}
