<?php declare(strict_types=1);

namespace Sparql;

if (!class_exists(\Common\TraitModule::class)) {
    require_once dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\Stdlib\PsrMessage;
use Common\TraitModule;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\MvcEvent;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Stdlib\Message;

/**
 * Sparql
 *
 * @copyright Daniel Berthereau, 2023-2024
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    protected $dependencies = [
        'Common',
    ];

    public function init(ModuleManager $moduleManager): void
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        /**
         * @var \Omeka\Permissions\Acl $acl
         * @see \Omeka\Service\AclFactory
         */
        $services = $this->getServiceLocator();
        $acl = $services->get('Omeka\Acl');

        $acl
            // Anybody can use the sparql endpoint.
            // TODO Use credentials in sparql like api.
            ->allow(
                null,
                [\Sparql\Controller\SparqlController::class],
                ['error', 'sparql']
            )
        ;
    }

    protected function preInstall(): void
    {
        $services = $this->getServiceLocator();
        $translator = $services->get('MvcTranslator');

        if (!method_exists($this, 'checkModuleActiveVersion') || !$this->checkModuleActiveVersion('Common', '3.4.62')) {
            $message = new Message(
                $translator->translate('The module %1$s should be upgraded to version %2$s or later.'), // @translate
                'Common', '3.4.62'
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }

        if (PHP_VERSION_ID < 80000) {
            $message = new PsrMessage(
                $translator->translate('The module requires php version {version} or newer, but your server has version {version_php}.'), // @translate
                ['version' => '8.0', 'version_php' => phpversion()]
            );
            throw new \Omeka\Module\Exception\ModuleCannotInstallException((string) $message);
        }

        $config = $services->get('Config');
        $basePath = $config['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');

        if (!$this->checkDestinationDir($basePath . '/triplestore')) {
            $message = new PsrMessage(
                'The directory "{directory}" is not writeable.', // @translate
                ['directory' => $basePath . '/triplestore']
            );
            throw new ModuleCannotInstallException((string) $message->setTranslator($translator));
        }
    }

    protected function postInstall(): void
    {
        $plugins = $this->getServiceLocator()->get('ControllerPluginManager');
        $urlPlugin = $plugins->get('url');
        $messenger = $plugins->get('messenger');

        if ($this->isModuleActive('DataTypeGeometry')
            && !$this->isModuleVersionAtLeast('DataTypeGeometry', '3.4.4')
        ) {
            $message = new PsrMessage(
                'The module DataTypeGeometry should be at least version 3.4.4 to index geographic and geometric values.', // @translate
            );
            $messenger->addWarning($message);
        }

        $message = new PsrMessage(
            'You should index your data first for the internal sparql server or for an external one. The internal one is available at {link} and a form can be anywhere via the site page block "sparql".', // @translate
            ['link' => $urlPlugin->fromRoute('sparql')]
        );
        $message->setEscapeHtml(false);
        $messenger->addSuccess($message);
    }

    protected function postUninstall(): void
    {
        $services = $this->getServiceLocator();
        $config = $services->get('Config');
        $basePath = $config['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');
        $dirPath = $basePath . '/triplestore';
        $this->rmDir($dirPath);
    }

    public function handleConfigForm(AbstractController $controller)
    {
        if (!$this->handleConfigFormAuto($controller)) {
            return false;
        }

        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');

        $fusekiEndpoint = $settings->get('sparql_fuseki_endpoint');
        if ($fusekiEndpoint) {
            $settings->set('sparql_fuseki_endpoint', rtrim($fusekiEndpoint, '/'));
        }

        if (!$settings->get('sparql_arc2_write_key')) {
            $writeKey = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(128))), 0, 24);
            $settings->set('sparql_arc2_write_key', $writeKey);
        }

        $params = $controller->getRequest()->getPost();
        if (empty($params['process'])) {
            return true;
        }

        $config = $services->get('Config');
        $plugins = $services->get('ControllerPluginManager');
        $urlPlugin = $plugins->get('url');
        $messenger = $plugins->get('messenger');

        $configModule = $config['sparql']['config'];
        $args = [
            'resource_types' => $settings->get('sparql_resource_types', $configModule['sparql_resource_types']),
            'resource_query' => $settings->get('sparql_resource_query', $configModule['sparql_resource_query']),
            'fields_included' => $settings->get('sparql_fields_included', $configModule['sparql_fields_included']),
            'property_whitelist' => $settings->get('sparql_property_whitelist', $configModule['sparql_property_whitelist']),
            'property_blacklist' => $settings->get('sparql_property_blacklist', $configModule['sparql_property_blacklist']),
            'datatype_whitelist' => $settings->get('sparql_datatype_whitelist', $configModule['sparql_datatype_whitelist']),
            'datatype_blacklist' => $settings->get('sparql_datatype_blacklist', $configModule['sparql_datatype_blacklist']),
            'arc2_write_key' => $settings->get('sparql_arc2_write_key', $configModule['sparql_arc2_write_key']),
            'fuseki_endpoint' => $settings->get('sparql_fuseki_endpoint', $configModule['sparql_fuseki_endpoint']),
            'fuseki_authmode' => $settings->get('sparql_fuseki_authmode', $configModule['sparql_fuseki_authmode']),
            'fuseki_username' => $settings->get('sparql_fuseki_username', $configModule['sparql_fuseki_username']),
            'fuseki_password' => $settings->get('sparql_fuseki_password', $configModule['sparql_fuseki_password']),
            'limit_per_page' => $settings->get('sparql_limit_per_page', $configModule['sparql_limit_per_page']),
            'indexes' => $settings->get('sparql_indexes', $configModule['sparql_indexes']),
        ];

        if (!in_array('html', $args['datatype_blacklist']) || !in_array('xml', $args['datatype_blacklist'])) {
            $message = new PsrMessage(
                'The data types html and xml are currently not supported and converted into literal.' // @translate
            );
            $messenger->addWarning($message);
        }

        if ($this->isModuleActive('DataTypeGeometry')
            && !$this->isModuleVersionAtLeast('DataTypeGeometry', '3.4.4')
            && (
                !in_array('geography', $args['datatype_blacklist'])
                || !in_array('geography:coordinates', $args['datatype_blacklist'])
                || !in_array('geometry', $args['datatype_blacklist'])
                || !in_array('geometry:coordinates', $args['datatype_blacklist'])
                || !in_array('geometry:position', $args['datatype_blacklist'])
            )
        ) {
            $message = new PsrMessage(
                'The module DataTypeGeometry should be at least version 3.4.4 to index geographic and geometric values.', // @translate
            );
            $messenger->addWarning($message);
        }

        // Use synchronous dispatcher for quick testing purpose.
        $strategy = null;
        $strategy = $strategy === 'synchronous'
            ? $this->getServiceLocator()->get(\Omeka\Job\DispatchStrategy\Synchronous::class)
            : null;

        $dispatcher = $services->get(\Omeka\Job\Dispatcher::class);
        $job = $dispatcher->dispatch(\Sparql\Job\IndexTriplestore::class, $args, $strategy);

        $message = new PsrMessage(
            'Indexing json-ld triplestore in background ({link_job}job #{job_id}{link_end}, {link_log}logs{link_end}).', // @translate
            [
                'link_job' => sprintf('<a href="%s">',
                    htmlspecialchars($urlPlugin->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId()]))
                ),
                'job_id' => $job->getId(),
                'link_end' => '</a>',
                'link_log' => sprintf('<a href="%s">',
                    htmlspecialchars($this->isModuleActive('Log')
                        ? $urlPlugin->fromRoute('admin/log', [], ['query' => ['job_id' => $job->getId()]])
                        : $urlPlugin->fromRoute('admin/id', ['controller' => 'job', 'id' => $job->getId(), 'action' => 'log'])
                    )
                ),
            ]
        );
        $message->setEscapeHtml(false);
        $messenger->addSuccess($message);
        return true;
    }
}
