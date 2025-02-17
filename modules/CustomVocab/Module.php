<?php
namespace CustomVocab;

use Composer\Semver\Comparator;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event)
    {
        parent::onBootstrap($event);

        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            'CustomVocab\Controller\Admin\Index',
            ['browse', 'show-details']
        );
        $acl->allow(
            null,
            \CustomVocab\Api\Adapter\CustomVocabAdapter::class,
            ['search', 'read']
        );
        $acl->allow(
            null,
            \CustomVocab\Entity\CustomVocab::class,
            ['read']
        );
        $acl->allow(
            'editor',
            'CustomVocab\Controller\Admin\Index',
            ['add', 'edit', 'delete']
        );
        $acl->allow(
            'editor',
            \CustomVocab\Api\Adapter\CustomVocabAdapter::class,
            ['create', 'update', 'delete']
        );
        $acl->allow(
            'editor',
            \CustomVocab\Entity\CustomVocab::class,
            'create'
        );
        $acl->allow(
            'editor',
            \CustomVocab\Entity\CustomVocab::class,
            ['update', 'delete'],
            new OwnsEntityAssertion
        );
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('CREATE TABLE custom_vocab (id INT AUTO_INCREMENT NOT NULL, item_set_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, `label` VARCHAR(190) NOT NULL, lang VARCHAR(190) DEFAULT NULL, terms LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", uris LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", UNIQUE INDEX UNIQ_8533D2A5EA750E8 (`label`), INDEX IDX_8533D2A5960278D7 (item_set_id), INDEX IDX_8533D2A57E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE custom_vocab ADD CONSTRAINT FK_8533D2A5960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE custom_vocab ADD CONSTRAINT FK_8533D2A57E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $conn = $serviceLocator->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE custom_vocab');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
        // Set all types to a default state.
        $conn->exec('UPDATE value SET type = "uri" WHERE type REGEXP "^customvocab:[0-9]+$" AND uri IS NOT NULL');
        $conn->exec('UPDATE value SET type = "literal" WHERE type REGEXP "^customvocab:[0-9]+$" AND value IS NOT NULL');
        $conn->exec('UPDATE value SET type = "resource:item" WHERE type REGEXP "^customvocab:[0-9]+$" AND value_resource_id IS NOT NULL');
        $conn->exec('UPDATE resource_template_property SET data_type = NULL WHERE data_type REGEXP "^customvocab:[0-9]+$"');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        if (Comparator::lessThan($oldVersion, '1.2.0')) {
            // Add the item set field
            $conn->exec('ALTER TABLE custom_vocab ADD item_set_id int(11) DEFAULT NULL');
            $conn->exec('ALTER TABLE custom_vocab ADD CONSTRAINT FK_8533D2A5960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;');
            // Make `terms` DEFAULT NULL
            $conn->exec('ALTER TABLE `custom_vocab` CHANGE `terms` `terms` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL;');
        }
        if (Comparator::lessThan($oldVersion, '1.4.0')) {
            // Add the URIs field
            $conn->exec('ALTER TABLE custom_vocab ADD uris LONGTEXT DEFAULT NULL');
        }
        if (Comparator::lessThan($oldVersion, '1.7.0')) {
            // Use old heuristic from representation to convert terms and uris.
            $vocabs = $conn->executeQuery('SELECT id, terms, uris FROM custom_vocab WHERE item_set_id IS NULL;')->fetchAll();
            foreach ($vocabs as $vocab) {
                $id = $vocab['id'];
                $uris = $vocab['uris'];
                $terms = $vocab['terms'];
                if ($uris) {
                    $result = [];
                    $matches = [];
                    foreach (array_filter(array_map('trim', explode("\n", $uris)), 'strlen') as $uri) {
                        if (preg_match('/^(\S+) \s*(.+)\s*$/', $uri, $matches)) {
                            $result[$matches[1]] = $matches[1] === $matches[2] ? '' : $matches[2];
                        } elseif (preg_match('/^\s*(.+)\s*/', $uri, $matches)) {
                            $result[$matches[1]] = '';
                        }
                    }
                    empty($result)
                        ? $conn->executeStatement('UPDATE custom_vocab SET uris = NULL, terms = NULL WHERE id = :id;', ['id' => $id])
                        : $conn->executeStatement('UPDATE custom_vocab SET uris = :uris, terms = NULL WHERE id = :id;', ['id' => $id, 'uris' => json_encode($result)]);
                } else {
                    $terms = array_filter(array_map('trim', explode("\n", $terms)), 'strlen') ?: null;
                    empty($terms)
                        ? $conn->executeStatement('UPDATE custom_vocab SET terms = NULL, uris = NULL WHERE id = :id;', ['id' => $id])
                        : $conn->executeStatement('UPDATE custom_vocab SET terms = :terms, uris = NULL WHERE id = :id;', ['id' => $id, 'terms' => json_encode($terms)]);
                }
            }
            $conn->executeStatement('ALTER TABLE custom_vocab CHANGE terms terms LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)", CHANGE uris uris LONGTEXT DEFAULT NULL COMMENT "(DC2Type:json)";');
        }
        if (Comparator::lessThan($oldVersion, '2.0.1')) {
            // Terms and URIs may not have been converted to arrays in the previous
            // migration. Here we iterate through every row, find unconverted terms
            // and URIs, and convert them to arrays.
            $sql = 'SELECT * FROM custom_vocab';
            $vocabs = $conn->executeQuery($sql)->fetchAll();
            foreach ($vocabs as $vocab) {
                if (!is_null($vocab['terms'])) {
                    if (!is_array(json_decode($vocab['terms'], true))) {
                        $terms = array_filter(array_map('trim', explode("\n", $vocab['terms'])), 'strlen') ?: null;
                        empty($terms)
                            ? $conn->executeStatement('UPDATE custom_vocab SET terms = NULL WHERE id = :id;', ['id' => $vocab['id']])
                            : $conn->executeStatement('UPDATE custom_vocab SET terms = :terms WHERE id = :id;', ['id' => $vocab['id'], 'terms' => json_encode($terms)]);
                    }
                }
                if (!is_null($vocab['uris'])) {
                    if (!is_array(json_decode($vocab['uris'], true))) {
                        $result = [];
                        foreach (array_filter(array_map('trim', explode("\n", $vocab['uris'])), 'strlen') as $uri) {
                            if (preg_match('/^(\S+) \s*(.+)\s*$/', $uri, $matches)) {
                                $result[$matches[1]] = ($matches[1] === $matches[2]) ? '' : $matches[2];
                            } elseif (preg_match('/^\s*(.+)\s*/', $uri, $matches)) {
                                $result[$matches[1]] = '';
                            }
                        }
                        empty($result)
                            ? $conn->executeStatement('UPDATE custom_vocab SET uris = NULL WHERE id = :id;', ['id' => $vocab['id']])
                            : $conn->executeStatement('UPDATE custom_vocab SET uris = :uris WHERE id = :id;', ['id' => $vocab['id'], 'uris' => json_encode($result)]);
                    }
                }
            }
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\DataType\Manager',
            'service.registered_names',
            [$this, 'addVocabularyServices']
        );
        $sharedEventManager->attach(
            \CustomVocab\Entity\CustomVocab::class,
            'entity.remove.pre',
            [$this, 'setVocabTypeToDefaultState']
        );
        $sharedEventManager->attach(
            '*',
            'csv_import.config',
            [$this, 'addDataTypesToCsvImportConfig']
        );
        $sharedEventManager->attach(
            '*',
            'data_types.value_annotating',
            [$this, 'addDataTypesToValueAnnotatingConfig']
        );
    }

    public function addVocabularyServices(Event $event)
    {
        $vocabs = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('custom_vocabs')->getContent();
        if (!$vocabs) {
            return;
        }
        $names = $event->getParam('registered_names');
        foreach ($vocabs as $vocab) {
            $names[] = 'customvocab:' . $vocab->id();
        }
        $event->setParam('registered_names', $names);
    }

    public function setVocabTypeToDefaultState(Event $event)
    {
        $vocab = $event->getTarget();
        $vocabName = 'customvocab:' . $vocab->getId();
        $conn = $this->getServiceLocator()->get('Omeka\Connection');

        $stmt = $conn->prepare('UPDATE value SET type = "literal" WHERE type = ?');
        $stmt->bindValue(1, $vocabName);
        $stmt->execute();

        $stmt = $conn->prepare('UPDATE resource_template_property SET data_type = NULL WHERE data_type = ?');
        $stmt->bindValue(1, $vocabName);
        $stmt->execute();
    }

    /**
     * Add Custom Vocab data types to CSV Import configuration.
     *
     * Typically we would do this by modifying the `csv_import` config array,
     * but we have to add them via CSVImport's `csv_import.config` event because
     * Custom Vocab data types are dynamically named.
     *
     * @param Event $event
     */
    public function addDataTypesToCsvImportConfig(Event $event)
    {
        $config = $event->getParam('config');
        $vocabs = $this->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->search('custom_vocabs')->getContent();
        foreach ($vocabs as $vocab) {
            // Build the data type name according to the convention established
            // by this module.
            $name = sprintf('customvocab:%s', $vocab->id());
            // Set the CSV Import data type "adapter" according to the type of
            // vocabulary, which is determined heuristically.
            $adapter = $vocab->type() ?? 'literal';
            $config['data_types'][$name] = [
                'label' => $vocab->label(),
                'adapter' => $adapter,
            ];
        }
        $event->setParam('config', $config);
    }

    /**
     * Add Custom Vocab data types as value annotating.
     *
     * @param Event $event
     */
    public function addDataTypesToValueAnnotatingConfig(Event $event)
    {
        $valueAnnotating = $event->getParam('data_types');
        $vocabs = $this->getServiceLocator()
            ->get('Omeka\ApiManager')
            ->search('custom_vocabs')->getContent();
        foreach ($vocabs as $vocab) {
            $valueAnnotating[] = sprintf('customvocab:%s', $vocab->id());
        }
        $event->setParam('data_types', $valueAnnotating);
    }
}
