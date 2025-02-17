<?php declare(strict_types=1);

namespace Sparql\View\Helper;

use ARC2;
use ARC2_Store;
use ARC2_StoreEndpoint;
use Doctrine\DBAL\Connection;
use EasyRdf\RdfNamespace;
use Exception;
use Laminas\Form\FormElementManager;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Mvc\Controller\Plugin\CurrentSite;
use Omeka\Mvc\Controller\Plugin\Messenger;
use Omeka\Settings\Settings;

class SparqlSearch extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/sparql-search';

    /**
     * The partial view script for yasgui.
     */
    const PARTIAL_NAME_YASGUI = 'common/sparql-search-yasgui';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\CurrentSite
     */
    protected $currentSite;

    /**
     * @var \Laminas\Form\FormElementManager
     */
    protected $formManager;

    /**
     * @var \Omeka\Mvc\Controller\Plugin\Messenger;
     */
    protected $messenger;

    /**
     * @var \Laminas\Mvc\Controller\Plugin\Params
     */
    protected $params;

    /**
     * @var \Omeka\Settings\Settings
     */
    protected $settings;

    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var int
     */
    protected $limitPerPage;

    public function __construct(
        Connection $connection,
        CurrentSite $currentSite,
        FormElementManager $formManager,
        Messenger $messenger,
        Params $params,
        Settings $settings,
        string $basePath,
        int $limitPerPage
    ) {
        $this->connection = $connection;
        $this->currentSite = $currentSite;
        $this->formManager = $formManager;
        $this->messenger = $messenger;
        $this->params = $params;
        $this->settings = $settings;
        $this->basePath = $basePath;
        $this->limitPerPage = $limitPerPage;
    }

    /**
     * Display the sparql search form.
     *
     * @param array $options
     * - template (string)
     * - method (string): get (default) or post
     * - sparqlArray (bool): return results according to sparl protocol v1.1.
     * - interface (string): "default" (default) or "yasgui".
     * - yasgui (array):
     *   - endpoint (string): url to use (default: see main config).
     * @return string|array Html string or result array.
     */
    public function __invoke(array $options = [])
    {
        $view = $this->getView();

        $options = $this->checkOptions($options);

        // TODO Manage proxy to fuseki with easyrdf client.

        if ($options['interface'] !== 'yasgui' || $options['yasgui']['internal']) {
            /** @var \ARC2_Store $triplestore */
            $triplestore = $this->getSparqlTriplestore($options['sparqlArray']);
            if (!$triplestore) {
                return $options['sparqlArray'] ? [] : '';
            }

            $result = $this->sparqlQueryTriplestore($triplestore);

            if (!empty($options['method'])) {
                $result['form']->setAttribute('method', $options['method']);
            }
        } else {
            $result = [
                'site' => $this->currentSite->__invoke(),
                'form' => null,
                'query' => $this->params->fromPost('query') ?: $this->params->fromQuery('query'),
                'result' => null,
                'format' => null,
                'triplestore' => null,
                'namespaces' => $this->prepareNamespaces(),
                'errorMessage' => null,
            ];
        }

        if ($options['sparqlArray']) {
            return $result;
        }

        $result += $options;
        unset($result['triplestore']);

        $template = empty($options['template'])
            ? ($options['interface'] === 'yasgui'
                ? self::PARTIAL_NAME_YASGUI
                : self::PARTIAL_NAME)
            : $options['template'];

        return $view->partial($template, $result);
    }

    /**
     * Get an ARC2 local store (simple or standard endpoint).
     *
     * Help on getStoreEndpoint() and getStore():
     * @see https://github.com/semsol/arc2/wiki/Getting-started-with-ARC2
     * @see https://github.com/semsol/arc2/wiki/SPARQL-Endpoint-Setup
     *
     * @see \Sparql\View\Helper\SparqlSearch::getSparqlTriplestore()
     * @see \Sparql\Job\IndexTriplestore::indexArc2()
     *
     * @return \ARC2_Store|\ARC2_StoreEndpoint|null
     */
    protected function getSparqlTriplestore(bool $isEndpoint = false): ?ARC2_Store
    {
        $writeKey = $this->settings->get('sparql_arc2_write_key') ?: '';
        $limitPerPage = (int) $this->settings->get('sparql_limit_per_page') ?: $this->limitPerPage;

        // Endpoint configuration.
        $db = $this->connection->getParams();
        $configArc2 = [
            // Database.
            'db_host' => $db['host'],
            'db_name' => $db['dbname'],
            'db_user' => $db['user'],
            'db_pwd' => $db['password'],

            // Network.
            // 'proxy_host' => '192.168.1.1',
            // 'proxy_port' => 8080,
            // Parsers.
            // 'bnode_prefix' => 'bn',
            // Semantic html extraction.
            // 'sem_html_formats' => 'rdfa microformats',

            // Store name.
            'store_name' => 'triplestore',

            // Endpoint.
            'endpoint_features' => [
                // Read requests.
                'select',
                'construct',
                'ask',
                'describe',
                // Write requests.
                // 'load',
                // 'insert',
                // 'delete',
                // Dump is a special command for streaming SPOG export.
                // 'dump',
            ],

            // TODO Add read/write key via Omeka credentials.
            // Not implemented in ARC2 preview.
            'endpoint_timeout' => 60,
            'endpoint_read_key' => '',
            'endpoint_write_key' => $writeKey,
            'endpoint_max_limit' => $limitPerPage,
        ];

        try {
            /** @var \ARC2_Store|\ARC2_StoreEndpoint $store */
            $store = $isEndpoint
                ? ARC2::getStoreEndpoint($configArc2)
                : ARC2::getStore($configArc2);
            $store->createDBCon();
            if (!$store->isSetUp()) {
                $store->setUp();
            }
        } catch (Exception $e) {
            $this->logger()->err($e);
            return null;
        }

        return $store;
    }

    protected function checkOptions(array $options): array
    {
        $options += [
            'template' => null,
            'method' => null,
            'sparqlArray' => false,
            'interface' => null,
            'yasgui' => [],
        ];

        $options['sparqlArray'] = (bool) $options['sparqlArray'];

        if ($options['interface'] === 'yasgui') {
            $plugins = $this->getView()->getHelperPluginManager();
            if (empty($options['yasgui']['endpoint'])) {
                $setting = $plugins->get('setting');
                $endpoint = $setting('sparql_endpoint');
                $endpointExternal = $setting('sparql_endpoint_external');
                if ($endpoint === 'none' || ($endpoint === 'external' && !$endpointExternal)) {
                    $options['yasgui']['endpoint'] = null;
                    $options['yasgui']['internal'] = false;
                } elseif (!$endpointExternal || $endpoint === 'internal') {
                    $urlHelper = $plugins->get('url');
                    $options['yasgui']['endpoint'] = $urlHelper('sparql', [], ['force_canonical' => true]);
                    $options['yasgui']['internal'] = true;
                } else {
                    $options['yasgui']['endpoint'] = $endpointExternal;
                    $options['yasgui']['internal'] = false;
                }
            } else {
                $urlHelper = $plugins->get('url');
                $options['yasgui']['internal'] = $options['yasgui']['endpoint'] === $urlHelper('sparql')
                    || $options['yasgui']['endpoint'] === $urlHelper('sparql', [], ['force_canonical' => true]);
            }
        } else {
            $options['interface'] = 'default';
        }

        return $options;
    }

    protected function sparqlQueryTriplestore(ARC2_Store $triplestore): array
    {
        /** @var \Sparql\Form\SparqlForm $form */
        $form = $this->formManager->get(\Sparql\Form\SparqlForm::class);
        $query = null;
        $result = null;
        $format = null;
        $namespaces = $this->prepareNamespaces();
        $includePrefixes = false;
        $errorMessage = null;

        // Allow query via post and get for end user and view simplicity.
        // It is required by sparql protocol anyway.
        $data = $this->params->fromPost()
            ?: $this->params->fromQuery();

        if ($data) {
            $form->setData($data);
            if ($form->isValid()) {
                $query = $data['query'] ?? null;
                $query = is_string($query) && trim($query) !== ''
                    ? trim($query)
                    : null;
                $format = ($data['format'] ?? null) === 'text' ? 'text' : 'html';
                if ($query) {
                    // TODO Check prepending prefixes: arc2 should work without them.
                    // Prepend all prefixes: only common ones are set.
                    $prefixes = '';
                    $includePrefixes = !empty($data['prepend_prefixes']);
                    foreach ($namespaces as $prefix => $iri) {
                        // Only the addition of prefixes in the query works.
                        $triplestore->setPrefix($prefix, $iri);
                        if ($includePrefixes) {
                            $prefixes .= "PREFIX $prefix: <$iri>\n";
                        }
                    }
                    // For better protocol handling, remove key submit.
                    // The key format is not used here.
                    unset(
                        $_GET['submit'],
                        $_POST['submit'],
                        $_GET['prepend_prefixes'],
                        $_POST['prepend_prefixes']
                    );
                    if ($includePrefixes) {
                        if (isset($_GET['query'])) {
                            $_GET['query'] = $prefixes . $_GET['query'];
                        }
                        if (isset($_POST['query'])) {
                            $_GET['query'] = $prefixes . $_POST['query'];
                        }
                    }
                    try {
                        // Deprecated in many places: passing null to preg_match in ARC2_Store line 304
                        $errorReporting = error_reporting();
                        error_reporting($errorReporting & ~E_DEPRECATED);
                        if ($triplestore instanceof ARC2_StoreEndpoint) {
                            $triplestore->handleRequest();
                            $result = $triplestore->getResult();
                        } else {
                            $result = $triplestore->query($prefixes . $query);
                        }
                        $errors = $triplestore->getErrors();
                        error_reporting($errorReporting);
                        if ($errors) {
                            $errorMessage = implode("\n", $errors);
                        }
                    } catch (Exception $e) {
                        $errorMessage = $e->getMessage();
                    }
                }
            } else {
                $this->messenger->addFormErrors($form);
            }
        }

        return [
            'site' => $this->currentSite->__invoke(),
            'form' => $form,
            'query' => $query,
            'result' => $result,
            'format' => $format,
            'triplestore' => $triplestore,
            'namespaces' => $namespaces,
            'errorMessage' => $errorMessage,
        ];
    }

    /**
     * Prepare all vocabulary prefixes used in the database.
     *
     * @todo Use context to create the list of prefixes and iris?
     * @see \Sparql\Job\IndexTriplestore::initPrefixesShort()
     */
    protected function prepareNamespaces(): array
    {
        $prefixIris = [
            'o' => 'http://omeka.org/s/vocabs/o#',
            'xsd' => 'http://www.w3.org/2001/XMLSchema#',
        ];

        if (in_array('rdfs:label', $this->settings->get('sparql_fields_included', []))) {
            $prefixIris['rdfs'] = 'http://www.w3.org/2000/01/rdf-schema#';
        }

        // TODO Check if data type geometry is used.
        if (class_exists('DataTypeGeometry\Entity\DataTypeGeography')) {
            $prefixIris['geo'] = 'http://www.opengis.net/ont/geosparql#';
        }

        $sql = <<<SQL
SELECT vocabulary.prefix, vocabulary.namespace_uri
FROM vocabulary
JOIN property ON property.vocabulary_id = vocabulary.id
JOIN value ON value.property_id = property.id
GROUP BY vocabulary.prefix
ORDER BY vocabulary.prefix ASC
;
SQL;
        $prefixIris += $this->connection->executeQuery($sql)->fetchAllKeyValue();

        $sql = <<<SQL
SELECT vocabulary.prefix, vocabulary.namespace_uri
FROM vocabulary
JOIN resource_class ON resource_class.vocabulary_id = vocabulary.id
JOIN resource ON resource.resource_class_id = resource_class.id
GROUP BY vocabulary.prefix
ORDER BY vocabulary.prefix ASC
;
SQL;
        $prefixIris += $this->connection->executeQuery($sql)->fetchAllKeyValue();

        foreach ($prefixIris as $prefix => $iri) {
            RdfNamespace::set($prefix, $iri);
        }

        ksort($prefixIris);

        return $prefixIris;
    }
}
