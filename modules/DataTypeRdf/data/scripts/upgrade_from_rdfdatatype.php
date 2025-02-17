<?php declare(strict_types=1);

namespace DataTypeRdf;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

$logger = $services->get('Omeka\Logger');

/** @var \Omeka\Module\Manager $moduleManager */
$moduleManager = $services->get('Omeka\ModuleManager');
$moduleRdfDatatype = $moduleManager->getModule('RdfDatatype');
$moduleNumericDataTypes = $moduleManager->getModule('NumericDataTypes');
if (!$moduleNumericDataTypes || $moduleNumericDataTypes->getState() !== \Omeka\Module\Manager::STATE_ACTIVE) {
    $moduleNumericDataTypes = null;
}

$sql = <<<'SQL'
SELECT COUNT(`id`) FROM `value`
WHERE type IN ("rdf:HTML", "rdf:XMLLiteral", "xsd:boolean", "xsd:date", "xsd:dateTime", "xsd:decimal", "xsd:gDay", "xsd:gMonth", "xsd:gMonthDay", "xsd:gYear", "xsd:gYearMonth", "xsd:integer", "xsd:time");
SQL;
$totalUsed = $connection->executeQuery($sql)->fetchOne();
$sql = <<<'SQL'
SELECT COUNT(DISTINCT(`resource_id`)) FROM `value`
WHERE type IN ("rdf:HTML", "rdf:XMLLiteral", "xsd:boolean", "xsd:date", "xsd:dateTime", "xsd:decimal", "xsd:gDay", "xsd:gMonth", "xsd:gMonthDay", "xsd:gYear", "xsd:gYearMonth", "xsd:integer", "xsd:time");
SQL;
$totalUsedResources = $connection->executeQuery($sql)->fetchOne();
$message = sprintf('A total of %1$s values in %2$s resources will be updated.', $totalUsed, $totalUsedResources);
$logger->notice($message);
$messenger->addNotice($message);

$numerics = [
    'xsd:date',
    'xsd:dateTime',
    'xsd:gYear',
    'xsd:gYearMonth',
    'xsd:integer',
];
$countNumerics = [];
foreach ($numerics as $datatype) {
    $sql = "SELECT COUNT(`id`) FROM `value` WHERE `type` = '$datatype';";
    $countNumerics[$datatype] = $connection->executeQuery($sql)->fetchOne();
}
$totalNumerics = array_sum($countNumerics);

$removeds = [
    'xsd:decimal',
    'xsd:gDay',
    'xsd:gMonth',
    'xsd:gMonthDay',
    'xsd:time',
];
$countRemoveds = [];
foreach ($removeds as $datatype) {
    $sql = "SELECT COUNT(`id`) FROM `value` WHERE `type` = '$datatype';";
    $countRemoveds[$datatype] = $connection->executeQuery($sql)->fetchOne();
}
$totalRemoveds = array_sum($countRemoveds);

$sql = <<<'SQL'
SELECT COUNT(`id`) FROM `value`
WHERE `type` = "xsd:dateTime"
AND (`value` LIKE "%+%" OR `value` LIKE "%:%-%");
SQL;
$totalTimeZonesUsed = $connection->executeQuery($sql)->fetchOne();

if ($totalRemoveds || $totalTimeZonesUsed) {
    $flagUpgrade = $settings->get('datatyperdf_flag_upgrade', false);
    if (!$flagUpgrade) {
        $flagUpgrade = $settings->set('datatyperdf_flag_upgrade', true);

        if ($totalRemoveds) {
            $messageCount = array_filter($countRemoveds);
            array_walk($messageCount, function (&$v, $k): void {
                $v = "$k ($v)";
            });
            $message = sprintf('Some resources contain values with a data type that is not managed (%s). They will be converted into "literal".',
                implode(', ', $messageCount)
            );
            $logger->err($message);
            $messenger->addError($message);
        }

        if ($totalTimeZonesUsed) {
            $message = sprintf('%d date values with a time zone cannot be updated and will be converted into "literal".',
                $totalTimeZonesUsed
            );
            $logger->err($message);
            $messenger->addError($message);
        }

        $datatypes = implode("', '", array_keys(array_filter($countRemoveds)));
        $sql = "SELECT DISTINCT(`resource_id`) FROM `value` WHERE `type` IN ('$datatypes') ORDER BY `resource_id` ASC;";
        $resourceIds = $connection->executeQuery($sql)->fetchFirstColumn();
        $message = sprintf('The list of resource ids with such values are: %s',
            implode(', ', $resourceIds)
        );
        $logger->warn($message);
        $messenger->addWarning($message);

        if ($totalNumerics && !$moduleNumericDataTypes) {
            $messageCount = array_filter($countNumerics);
            array_walk($messageCount, function (&$v, $k): void {
                $v = "$k ($v)";
            });
            $message = sprintf('Furthermore, the module Numeric Data Types is required to upgrade other data automatically: %s',
                implode(', ', $messageCount)
            );
            $logger->warn($message);
            $messenger->addWarning($message);
        }

        throw new \Omeka\Module\Exception\ModuleCannotInstallException(
            'You should backup your database to keep these data safe, then you need to click on "Install" a second time to install the module. Of course, you should check your backup too.'
        );
    }
}

if ($totalNumerics && !$moduleNumericDataTypes) {
    $messageCount = array_filter($countNumerics);
    array_walk($messageCount, function (&$v, $k): void {
        $v = "$k ($v)";
    });
    $message = sprintf('The module Numeric Data Types is required to upgrade some data automatically: %s',
        implode(', ', $messageCount)
    );
    $logger->warn($message);
    throw new \Omeka\Module\Exception\ModuleCannotInstallException($message);
}

$logger->notice('Processing upgrade of module RdfDatatype.');

// Clean settings.
$settings->delete('datatyperdf_flag_upgrade');
$settings->delete('rdfdatatype_datatypes');

// Upgrade to new data type name.
$map = [
    'rdf:HTML' => 'html',
    'rdf:XMLLiteral' => 'xml',
    'xsd:boolean' => 'boolean',
];
foreach ($map as $old => $new) {
    $sql = "UPDATE `value` SET `type` = '$new' WHERE `type` = '$old';";
    $connection->executeStatement($sql);
}

$message = 'Values with data types "html", "xml" and "boolean" were upgraded sucessfully.';
$logger->notice($message);
$messenger->addNotice($message);

// Convert unmanaged data types to literal.
$olds = implode("', '", $removeds);
$sql = "UPDATE `value` SET `type` = 'literal' WHERE `type` IN ('$olds');";
$connection->executeStatement($sql);
if ($totalRemoveds) {
    $messageCount = array_filter($countRemoveds);
    array_walk($messageCount, function (&$v, $k): void {
        $v = "$k ($v)";
    });
    $message = sprintf('Values with unmanaged data types were converted into "literal" sucessfully: %s.', implode(', ', $messageCount));
    $logger->warn($message);
    $messenger->addWarning($message);
} else {
    $message = sprintf('Values with data types "%s" were converted into "literal" sucessfully (no values).', $olds);
    $logger->notice($message);
    $messenger->addNotice($message);
}

if ($totalNumerics) {
    // Convert integer.
    $sql = <<<SQL
INSERT INTO `numeric_data_types_integer` (`resource_id`, `property_id`, `value`)
SELECT `resource_id`, `property_id`, `value`
FROM `value`
WHERE `type` = "xsd:integer";
SQL;
    $connection->executeStatement($sql);
    $sql = "UPDATE `value` SET `type` = 'numeric:integer' WHERE `type` = 'xsd:integer';";
    $connection->executeStatement($sql);

    $message = 'Values with data type "integer" were upgraded sucessfully.';
    $logger->notice($message);
    $messenger->addNotice($message);

    // Manage an exception when there is a timezone.
    if ($totalTimeZonesUsed) {
        $sql = "UPDATE `value` SET `type` = 'literal' WHERE `type` = 'xsd:dateTime' AND (`value` LIKE '%+%' OR `value` LIKE '%:%-%');";
        $connection->executeStatement($sql);
        $message = sprintf('Values with data type "xsd:dateTime" with a time zone were upgraded into "literal" sucessfully (%d).', $totalTimeZonesUsed);
        $logger->warn($message);
        $messenger->addWarning($message);
    }

    // Convert dates.
    foreach (['xsd:dateTime', 'xsd:date', 'xsd:gYear', 'xsd:gYearMonth'] as $datatype) {
        $sqlSelect = <<<SQL
SELECT `id`, `resource_id`, `property_id`, `value`
FROM `value`
WHERE `type` = "$datatype";
SQL;
        $sqlInsert = <<<'DQL'
INSERT INTO `numeric_data_types_timestamp` (`resource_id`, `property_id`, `value`) VALUES (:resource_id, :property_id, :value);
DQL;
        $stmt = $connection->executeQuery($sqlSelect);
        while ($row = $stmt->fetch()) {
            try {
                $date = \NumericDataTypes\DataType\Timestamp::getDateTimeFromValue($row['value']);
            } catch (\Exception $e) {
                $message = sprintf('For resource #%1$s, property #%2$s, the value "%3$s" cannot be converted into timestamp, but only to literal.',
                    $row['resource_id'], $row['property_id'], $row['value']);
                $rowId = $row['id'];
                $sql = "UPDATE `value` SET `type` = 'literal' WHERE `id` = '$rowId';";
                $connection->executeStatement($sql);
                $logger->err($message);
                $messenger->addError($message);
                continue;
            }
            $bind = [
                'resource_id' => $row['resource_id'],
                'property_id' => $row['property_id'],
                'value' => $date['date']->getTimestamp(),
            ];
            $result = $connection->executeStatement($sqlInsert, $bind);
            if (!$result) {
                $message = sprintf('An issue occurred when inserting data for resource #%1$s, property #%2$s with value "%3$s".',
                    $row['resource_id'], $row['property_id'], $row['value']);
                $logger->err($message);
                $messenger->addError($message);
            }
        }

        $connection->executeStatement($sql);
        $sql = "UPDATE `value` SET `type` = 'numeric:timestamp' WHERE `type` = '$datatype';";
        $connection->executeStatement($sql);

        $message = sprintf('Values with data type "%s" were upgraded into "numeric:timestamp" sucessfully.', $datatype);
        $logger->notice($message);
        $messenger->addNotice($message);
    }
}

if ($moduleRdfDatatype) {
    $module = $moduleRdfDatatype;
    $state = $module->getState();
    if (in_array($state, [
        \Omeka\Module\Manager::STATE_ACTIVE,
        \Omeka\Module\Manager::STATE_NOT_ACTIVE,
        \Omeka\Module\Manager::STATE_NOT_FOUND,
        \Omeka\Module\Manager::STATE_NEEDS_UPGRADE,
        \Omeka\Module\Manager::STATE_INVALID_OMEKA_VERSION,
    ])) {
        $t = $services->get('MvcTranslator');

        // Process uninstallation directly: the module has nothing to uninstall.
        $entity = $entityManager
            ->getRepository(\Omeka\Entity\Module::class)
            ->findOneById($module->getId());
        if ($entity) {
            $entityManager->remove($entity);
            $entityManager->flush();
            $message = new \Omeka\Stdlib\Message(
                $t->translate('The module RdfDatatype was automatically uninstalled.') // @translate
            );
            $messenger->addNotice($message);
            $module->setState(\Omeka\Module\Manager::STATE_NOT_INSTALLED);
        } else {
            $message = new \Omeka\Stdlib\Message(
                $t->translate('The module RdfDatatype cannot be automatically uninstalled.') // @translate
            );
            $messenger->addWarning($message);
        }
    }
}
