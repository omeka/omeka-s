<?php
namespace Collecting;

use Collecting\Permissions\Assertion\HasInputTextPermissionAssertion;
use Collecting\Permissions\Assertion\HasSitePermissionAssertion;
use Collecting\Permissions\Assertion\HasUserEmailPermissionAssertion;
use Collecting\Permissions\Assertion\HasUserNamePermissionAssertion;
use Composer\Semver\Comparator;
use Omeka\Module\AbstractModule;
use Omeka\Permissions\Assertion\OwnsEntityAssertion;
use Omeka\Permissions\Assertion\SiteIsPublicAssertion;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;
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
        $this->addAclRules();
    }

    public function install(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        // Reduce installation time by toggling off foreign key checks.
        $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
        $conn->exec('CREATE TABLE collecting_form (id INT AUTO_INCREMENT NOT NULL, item_set_id INT DEFAULT NULL, site_id INT NOT NULL, owner_id INT DEFAULT NULL, `label` VARCHAR(255) NOT NULL, anon_type VARCHAR(255) NOT NULL, success_text LONGTEXT DEFAULT NULL, email_text LONGTEXT DEFAULT NULL, default_site_assign TINYINT(1) DEFAULT NULL, INDEX IDX_99878BDD960278D7 (item_set_id), INDEX IDX_99878BDDF6BD1646 (site_id), INDEX IDX_99878BDD7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE collecting_item (id INT AUTO_INCREMENT NOT NULL, item_id INT NOT NULL, form_id INT NOT NULL, collecting_user_id INT NOT NULL, reviewer_id INT DEFAULT NULL, user_name VARCHAR(255) DEFAULT NULL, user_email VARCHAR(255) DEFAULT NULL, anon TINYINT(1) DEFAULT NULL, reviewed TINYINT(1) NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_D414538C126F525E (item_id), INDEX IDX_D414538C5FF69B7D (form_id), INDEX IDX_D414538CB0237C21 (collecting_user_id), INDEX IDX_D414538C70574616 (reviewer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE collecting_user (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_469CA0DBA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE collecting_input (id INT AUTO_INCREMENT NOT NULL, prompt_id INT NOT NULL, collecting_item_id INT NOT NULL, text LONGTEXT NOT NULL, INDEX IDX_C6E2CFC9B5C4AA38 (prompt_id), INDEX IDX_C6E2CFC9522FDEA (collecting_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->exec('CREATE TABLE collecting_prompt (id INT AUTO_INCREMENT NOT NULL, form_id INT NOT NULL, property_id INT DEFAULT NULL, position INT NOT NULL, type VARCHAR(255) NOT NULL, text LONGTEXT DEFAULT NULL, input_type VARCHAR(255) DEFAULT NULL, select_options LONGTEXT DEFAULT NULL, resource_query LONGTEXT DEFAULT NULL, custom_vocab INT DEFAULT NULL, media_type VARCHAR(255) DEFAULT NULL, required TINYINT(1) NOT NULL, INDEX IDX_98FE9BA65FF69B7D (form_id), INDEX IDX_98FE9BA6549213EC (property_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;');
        $conn->exec('ALTER TABLE collecting_form ADD CONSTRAINT FK_99878BDD960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE collecting_form ADD CONSTRAINT FK_99878BDDF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_form ADD CONSTRAINT FK_99878BDD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE collecting_item ADD CONSTRAINT FK_D414538C126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_item ADD CONSTRAINT FK_D414538C5FF69B7D FOREIGN KEY (form_id) REFERENCES collecting_form (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_item ADD CONSTRAINT FK_D414538CB0237C21 FOREIGN KEY (collecting_user_id) REFERENCES collecting_user (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_item ADD CONSTRAINT FK_D414538C70574616 FOREIGN KEY (reviewer_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE collecting_user ADD CONSTRAINT FK_469CA0DBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;');
        $conn->exec('ALTER TABLE collecting_input ADD CONSTRAINT FK_C6E2CFC9B5C4AA38 FOREIGN KEY (prompt_id) REFERENCES collecting_prompt (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_input ADD CONSTRAINT FK_C6E2CFC9522FDEA FOREIGN KEY (collecting_item_id) REFERENCES collecting_item (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_prompt ADD CONSTRAINT FK_98FE9BA65FF69B7D FOREIGN KEY (form_id) REFERENCES collecting_form (id) ON DELETE CASCADE;');
        $conn->exec('ALTER TABLE collecting_prompt ADD CONSTRAINT FK_98FE9BA6549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE;');
        $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function uninstall(ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        $conn->exec('SET FOREIGN_KEY_CHECKS=0;');
        $conn->exec('DROP TABLE IF EXISTS collecting_item;');
        $conn->exec('DROP TABLE IF EXISTS collecting_prompt;');
        $conn->exec('DROP TABLE IF EXISTS collecting_form;');
        $conn->exec('DROP TABLE IF EXISTS collecting_input;');
        $conn->exec('DROP TABLE IF EXISTS collecting_user;');
        $conn->exec('SET FOREIGN_KEY_CHECKS=1;');
        $conn->exec('DELETE FROM site_page_block WHERE layout = "collecting";');
        $conn->exec('DELETE FROM site_setting WHERE id = "collecting_tos";');
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $services)
    {
        $conn = $services->get('Omeka\Connection');
        if (Comparator::lessThan($oldVersion, '1.0.0-beta4')) {
            $conn->exec('ALTER TABLE collecting_prompt ADD resource_query LONGTEXT DEFAULT NULL AFTER select_options');
        }
        if (Comparator::lessThan($oldVersion, '1.1.0-alpha')) {
            $conn->exec('ALTER TABLE collecting_prompt ADD custom_vocab INT DEFAULT NULL AFTER resource_query');
        }
        if (Comparator::lessThan($oldVersion, '1.8.0')) {
            $conn->exec('ALTER TABLE collecting_form ADD default_site_assign TINYINT(1) DEFAULT NULL AFTER email_text');
        }
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_elements',
            [$this, 'addSiteSettings']
        );

        $sharedEventManager->attach(
            'Omeka\Form\SiteSettingsForm',
            'form.add_input_filters',
            [$this, 'addSiteSettingsInputFilters']
        );

        $sharedEventManager->attach(
            'Collecting\Api\Adapter\CollectingFormAdapter',
            'api.search.query',
            [$this, 'filterCollectingForms']
        );

        $sharedEventManager->attach(
            '*',
            'sql_filter.resource_visibility',
            [$this, 'filterSqlResourceVisibility']
        );

        // Add collecting data to the item show page.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            [$this, 'handleAdminShowAfter']
        );

        $sharedEventManager->attach(
            'Omeka\Controller\Site\Item',
            'view.show.after',
            [$this, 'handlePublicShowAfter']
        );

        // Add the collecting tab to the item show section navigation.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            [$this, 'filterSectionNav']
        );

        // Add the Collecting term definition to the JSON-LD context.
        $sharedEventManager->attach(
            '*',
            'api.context',
            [$this, 'filterApiContext']
        );
        // Copy Collecting-related data for the CopyResources module.
        $sharedEventManager->attach(
            '*',
            'copy_resources.sites.post',
            function (Event $event) {
                $api = $this->getServiceLocator()->get('Omeka\ApiManager');
                $site = $event->getParam('resource');
                $siteCopy = $event->getParam('resource_copy');
                $copyResources = $event->getParam('copy_resources');

                $copyResources->revertSiteBlockLayouts($siteCopy->id(), 'collecting');

                // Copy collecting forms.
                $collectingForms = $api->search('collecting_forms', ['site_id' => $site->id()])->getContent();
                $collectingFormMap = [];
                foreach ($collectingForms as $collectingForm) {
                    $callback = function (&$jsonLd) use ($siteCopy) {
                        unset($jsonLd['o:owner']);
                        $jsonLd['o:site']['o:id'] = $siteCopy->id();
                    };
                    $collectingFormCopy = $copyResources->createResourceCopy('collecting_forms', $collectingForm, $callback);
                    $collectingFormMap[$collectingForm->id()] = $collectingFormCopy->id();
                }

                // Modify block data.
                $callback = function (&$data) use ($collectingFormMap) {
                    if (isset($data['forms']) && is_array($data['forms'])) {
                        foreach ($data['forms'] as $index => $id) {
                            $data['forms'][$index] = array_key_exists($id, $collectingFormMap) ? $collectingFormMap[$id] : $id;
                        }
                    }
                };
                $copyResources->modifySiteBlockData($siteCopy->id(), 'collecting', $callback);
            }
        );
    }

    /**
     * Add elements to the site settings form.
     *
     * @param Event $event
     */
    public function addSiteSettings(Event $event)
    {
        $services = $this->getServiceLocator();
        $siteSettings = $services->get('Omeka\Settings\Site');
        $form = $event->getTarget();

        $groups = $form->getOption('element_groups');
        $groups['collecting'] = 'Collecting'; // @translate
        $form->setOption('element_groups', $groups);

        // Add the terms of service and email address to the form.
        $form->add([
            'type' => 'textarea',
            'name' => 'collecting_tos',
            'options' => [
                'element_group' => 'collecting',
                'label' => 'Terms of service', // @translate
                'info' => 'Enter the terms of service (TOS) for users who submit content to this site.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('collecting_tos'),
            ],
        ]);
        $form->add([
            'type' => 'url',
            'name' => 'collecting_tos_url',
            'options' => [
                'element_group' => 'collecting',
                'label' => 'Terms of service URL', // @translate
                'info' => 'Enter the URL to the terms of service (TOS) for users who submit content to this site.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('collecting_tos_url'),
            ],
        ]);
        $form->add([
            'type' => 'email',
            'name' => 'collecting_email',
            'options' => [
                'element_group' => 'collecting',
                'label' => 'Submission email address', // @translate
                'info' => 'Enter an email address from which user submission emails will be sent.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('collecting_email'),
            ],
        ]);
        $form->add([
            'type' => 'email',
            'name' => 'collecting_email_notify',
            'options' => [
                'element_group' => 'collecting',
                'label' => 'Notification email address', // @translate
                'info' => 'Enter an email address to which admin notification emails will be sent.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('collecting_email_notify'),
            ],
        ]);
        $form->add([
            'type' => 'checkbox',
            'name' => 'collecting_hide_collected_data',
            'options' => [
                'element_group' => 'collecting',
                'label' => 'Hide collected data', // @translate
                'info' => 'Hide the link to and prevent direct linking to collected data.', // @translate
            ],
            'attributes' => [
                'value' => $siteSettings->get('collecting_hide_collected_data'),
            ],
        ]);
    }

    /**
     * Add input filters to the site settings form.
     *
     * @param Event $event
     */
    public function addSiteSettingsInputFilters(Event $event)
    {
        $inputFilter = $event->getParam('inputFilter');
        $inputFilter->add([
            'name' => 'collecting_tos_url',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'collecting_email',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'collecting_email_notify',
            'required' => false,
            'allow_empty' => true,
        ]);
    }

    /**
     * Filter private collecting forms.
     *
     * @param Event $event
     */
    public function filterCollectingForms(Event $event)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if ($acl->userIsAllowed('Omeka\Entity\Site', 'view-all')) {
            return;
        }

        $adapter = $event->getTarget();
        $qb = $event->getParam('queryBuilder');

        // Users can view collecting forms they do not own that are public.
        $siteAlias = $adapter->createAlias();
        $qb->join('omeka_root.site', $siteAlias);
        $expression = $qb->expr()->eq("$siteAlias.isPublic", true);

        $identity = $this->getServiceLocator()
            ->get('Omeka\AuthenticationService')->getIdentity();
        if ($identity) {
            $sitePermissionAlias = $adapter->createAlias();
            $qb->leftJoin("$siteAlias.sitePermissions", $sitePermissionAlias);

            $expression = $qb->expr()->orX(
                $expression,
                // Users can view all collecting forms they own.
                $qb->expr()->eq(
                    "omeka_root.owner",
                    $adapter->createNamedParameter($qb, $identity)
                ),
                // Users can view sites where they have a role (any role).
                $qb->expr()->eq(
                    "$sitePermissionAlias.user",
                    $adapter->createNamedParameter($qb, $identity)
                )
            );
        }
        $qb->andWhere($expression);
    }

    public function filterSqlResourceVisibility(Event $event)
    {
        // Users can view collecting items only if they have permission
        // to view the attached item.
        $relatedEntities = $event->getParam('relatedEntities');
        $relatedEntities['Collecting\Entity\CollectingItem'] = 'item_id';
        $event->setParam('relatedEntities', $relatedEntities);
    }

    public function handleAdminShowAfter(Event $event)
    {
        $view = $event->getTarget();
        $cItem = $view->api()
            ->searchOne('collecting_items', ['item_id' => $view->item->id()])
            ->getContent();
        if (!$cItem) {
            // Don't render the partial if there's no collecting item.
            return;
        }
        echo $view->partial('common/collecting-item-section', ['cItem' => $cItem]);
    }

    public function handlePublicShowAfter(Event $event)
    {
        $view = $event->getTarget();
        if ($view->siteSetting('collecting_hide_collected_data')) {
            // Don't render the link if configured to hide it.
            return;
        }
        $cItem = $view->api()
            ->searchOne('collecting_items', ['item_id' => $view->item->id()])
            ->getContent();
        if (!$cItem) {
            // Don't render the link if there's no collecting item.
            return;
        }
        echo '<p>' . $cItem->displayCitation() . '</p>';
        echo $view->hyperlink(
            $view->translate('Click here to view the collected data.'),
            $view->url('site/collecting-item', [
                'site-slug' => $view->site->slug(),
                'item-id' => $cItem->id(),
            ])
        );
    }

    public function filterSectionNav(Event $event)
    {
        $view = $event->getTarget();
        $cItem = $view->api()
            ->searchOne('collecting_items', ['item_id' => $view->item->id()])
            ->getContent();
        if (!$cItem) {
            // Don't render the tab if there's no collecting item.
            return;
        }
        $sectionNav = $event->getParam('section_nav');
        $sectionNav['collecting-section'] = 'Collecting';
        $event->setParam('section_nav', $sectionNav);
    }

    public function filterApiContext(Event $event)
    {
        $context = $event->getParam('context');
        $context['o-module-collecting'] = 'http://omeka.org/s/vocabs/module/collecting#';
        $event->setParam('context', $context);
    }

    /**
     * Add ACL rules for this module.
     */
    protected function addAclRules()
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        $acl->allow(
            null,
            'Collecting\Controller\Site\Index'
        );
        $acl->allow(
            null,
            [
                'Collecting\Controller\SiteAdmin\Form',
                'Collecting\Controller\SiteAdmin\Item',
            ]
        );
        $acl->allow(
            null,
            [
                'Collecting\Api\Adapter\CollectingFormAdapter',
                'Collecting\Api\Adapter\CollectingItemAdapter',
            ]
        );

        // Give "create" privilege to every role so permission checks fall to
        // the site-specific "add-collecting-form" privilege (checked in the
        // CollectingFormAdapter API adapter).
        $acl->allow(
            null,
            'Collecting\Entity\CollectingForm',
            'create'
        );

        $acl->allow(
            null,
            'Omeka\Entity\Site',
            'add-collecting-form',
            new HasSitePermissionAssertion('admin')
        );
        $adminAssertion = new AssertionAggregate;
        $adminAssertion->addAssertions([
            new OwnsEntityAssertion,
            new HasSitePermissionAssertion('admin'),
        ]);
        $adminAssertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            [
                'Collecting\Entity\CollectingForm',
                'Collecting\Entity\CollectingItem',
            ],
            'delete',
            $adminAssertion
        );

        $editorAssertion = new AssertionAggregate;
        $editorAssertion->addAssertions([
            new OwnsEntityAssertion,
            new HasSitePermissionAssertion('editor'),
        ]);
        $editorAssertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            [
                'Collecting\Entity\CollectingForm',
                'Collecting\Entity\CollectingItem',
            ],
            'update',
            $editorAssertion
        );

        $viewerAssertion = new AssertionAggregate;
        $viewerAssertion->addAssertions([
            new SiteIsPublicAssertion,
            new OwnsEntityAssertion,
            new HasSitePermissionAssertion('viewer'),
        ]);
        $viewerAssertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            [
                'Collecting\Entity\CollectingForm',
                'Collecting\Entity\CollectingItem',
            ],
            'read',
            $viewerAssertion
        );

        // Discrete data permissions.
        $assertion = new AssertionAggregate;
        $assertion->addAssertions([
            new HasInputTextPermissionAssertion,
            new HasSitePermissionAssertion('editor'),
        ]);
        $assertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            'Collecting\Entity\CollectingInput',
            'view-collecting-input-text',
            $assertion
        );
        $assertion = new AssertionAggregate;
        $assertion->addAssertions([
            new HasUserNamePermissionAssertion,
            new HasSitePermissionAssertion('editor'),
        ]);
        $assertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
            null,
            'Collecting\Entity\CollectingItem',
            'view-collecting-user-name',
            $assertion
        );
        $assertion = new AssertionAggregate;
        $assertion->addAssertions([
            new HasUserEmailPermissionAssertion,
            new HasSitePermissionAssertion('editor'),
        ]);
        $assertion->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $acl->allow(
             null,
            'Collecting\Entity\CollectingItem',
            'view-collecting-user-email',
            $assertion
        );
    }
}
