<?php
namespace GraphDBDataSync\Controller\Admin;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Omeka\Permissions\Acl;
use Omeka\Mvc\Controller\Plugin\Messenger;
use GraphDBDataSync\Form\GraphDBConfigForm;
use Laminas\Config\Writer\Ini as IniWriter;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\Http\Client;
use Laminas\Http\Request;

use Laminas\Json\Json;
use Laminas\Mvc\Controller\PluginManager as PluginManager;
use Laminas\Form\FormElementManager as FormElementManager;

use Laminas\Mvc\InjectApplicationEventInterface;
use Laminas\EventManager\EventInterface;

class IndexController extends AbstractActionController implements InjectApplicationEventInterface
{
    private $acl;
    private $messenger;
    private $configPath;
    private $httpClient;
    private $pluginManager;
    private $formElementManager;

    private $config; 

    private $urlHelper;

    public function __construct(
        Acl $acl,
        Messenger $messenger,
        Client $httpClient,
        PluginManager $pluginManager,
        FormElementManager $formElementManager,
        $urlHelper 
    ) {
        $this->acl = $acl;
        $this->messenger = $messenger;
        $this->httpClient = $httpClient;
        $this->pluginManager = $pluginManager;
        $this->formElementManager = $formElementManager;
        $this->urlHelper = $urlHelper;
    }

    public function indexAction()
    {
        if (!$this->acl->isAllowed(null, 'GraphDBDataSync\Controller\Admin\Index', 'browse')) {
            $this->raise403($this->translate('You do not have permission to access this page.'));
        }

        $form = $this->getForm();
        $config = $this->getModuleConfig();
        if (isset($config['graphdb_endpoint'])) {
            $form->setData($config);
        }

        $view = new ViewModel(['form' => $form]);
        return $view;
    }

    public function setEvent(EventInterface $event)
    {
        $this->event = $event;
        return $this;
    }

    public function getEvent()
    {
        return $this->event;
    }

    public function configAction()
    {
        if (!$this->acl->isAllowed(null, 'GraphDBDataSync\Controller\Admin\Index', 'edit')) {
            $this->raise403($this->translate('You do not have permission to access this page.'));
        }

        $form = $this->getForm();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $config = $this->config;
                $data = $form->getData();

                $config['graphdb_sync'] = [
                    'graphdb_endpoint' => 'http://localhost:7200/repositories/arch-project-shacl',
                    'graphdb_username' => 'admin',
                    'graphdb_password' => 'admin',
                ];

                $writer = new IniWriter();
                $iniData = $writer->toString(['graphdb_sync' => $config['graphdb_sync']]);
                file_put_contents(OMEKA_PATH . '/config/graphdb_sync.ini', $iniData);

                $this->messenger()->addSuccess('GraphDB configuration saved.');
                return $this->redirect()->toRoute('admin/graphdb_data_sync');
            } else {
                $this->messenger()->addError('Invalid form data. Please check the fields.');
            }
        }

        $view = new ViewModel(['form' => $form]);
        $view->setTemplate('graph-db-data-sync/admin/index/index');
        return $view;
    }

    public function extractDataAction()
    {
        if (!$this->acl->isAllowed(null, 'GraphDBDataSync\Controller\Admin\Index', 'sync')) {
            $this->raise403($this->translate('You do not have permission to synchronize data.'));
        }

        $omekaData = $this->getOmekaItems();
        // For now, let's just display the data
        $viewModel = new ViewModel(['omekaData' => $omekaData]);
        $viewModel->setTemplate('graph-db-data-sync/admin/index/extract-data');
        return $viewModel;
    }

    private function getForm()
    {
        return $this->formElementManager->get(GraphDBConfigForm::class);
    }

    private function getModuleConfig()
    {
        $reader = new IniReader();
        $config = [];
        //$configFile = OMEKA_PATH . '/config/graphdb_sync.ini';
        //if (file_exists($configFile)) {
        //    $config = $reader->fromFile($configFile);
        //}

        // Default configuration if not set in the INI file
        $defaultConfig = [
            'graphdb_endpoint' => 'http://localhost:7200/repositories/arch-project-shacl',
            'graphdb_username' => 'admin',
            'graphdb_password' => 'admin',
        ];

        return array_merge($defaultConfig, $config['graphdb_sync'] ?? []);
    }

    private function getCollectingItems()
    {
        $collectingItems = [];
        try {
            // Adapte 'resource' para 'collecting_items'
            $apiUrl = $this->url()->fromRoute('api/default', [
                'resource' => 'collecting_items' // <-- Alterado
            ], ['force_canonical' => true]);

            $this->httpClient->setUri($apiUrl);
            $this->httpClient->setMethod('GET');
            // Adicionar autenticação à API Omeka S, se necessário
            // $this->httpClient->setAuth(...);
            $response = $this->httpClient->send();

            if ($response->isSuccess()) {
                $collectingItems = Json::decode($response->getBody(), Json::TYPE_ARRAY);
            } else {
                $this->messenger->addError($this->translate('Collecting API request failed with status: %s'), $response->getStatusCode());
                error_log('Collecting API Error: ' . $response->getBody()); // Log para depuração
            }
        } catch (\Exception $e) {
            $this->messenger->addError($this->translate('Collecting API connection error: %s'), $e->getMessage());
            error_log('Collecting API Exception: ' . $e->getMessage()); // Log para depuração
        }
        echo $collectingItems;
        return $collectingItems;
    }

    public function syncToGraphDbAction()
    {
        // 1. Verificar Permissão
        if (!$this->acl->isAllowed(null, 'GraphDBDataSync\Controller\Admin\Index', 'sync')) {
            return $this->raise403($this->translate('You do not have permission to synchronize data.'));
        }
        error_log('Sync to GraphDB action started.', 3, OMEKA_PATH . '/logs/graphdb-sync.log');

        // 2. Obter Configuração do GraphDB
        $graphDbConfig = $this->getModuleConfig();
        if (empty($graphDbConfig['graphdb_endpoint'])) {
            $this->messenger->addError($this->translate('GraphDB endpoint is not configured. Please configure it first.'));
            return $this->redirect()->toRoute('admin/graphdb_data_sync');
        }

        // 3. Obter Dados do Collecting
        $collectingItems = $this->getCollectingItems();
        error_log('Collecting items retrieved: ' . print_r($collectingItems, true), 3, OMEKA_PATH . '/logs/graphdb-sync.log');
        if (empty($collectingItems)) {
            $this->messenger->addInfo($this->translate('No collecting items found to synchronize.'));
            // Redirecionar para a página de extração ou índice
            return $this->redirect()->toRoute('admin/graphdb_data_sync/extract');
        }

        // 4. Transformar dados para TTL
        foreach ($collectingItems as $item) {
            $ttlString = $this->transformToTtl([$item]); // Processa cada item individualmente
            if (empty($ttlString)) {
            $this->messenger->addWarning($this->translate('Could not generate TTL data for an item.'));
            continue; // Pula para o próximo item
            }
            error_log('TTL String: ' . $ttlString, 3, OMEKA_PATH . '/logs/graphdb-sync.log');

            // Enviar TTL para o GraphDB
            $success = $this->sendTtlToGraphDb($ttlString, $graphDbConfig);

            if ($success) {
            $this->messenger->addSuccess($this->translate('Item successfully synchronized with GraphDB.'));
            } else {
            $this->messenger->addError($this->translate('Failed to synchronize an item with GraphDB.'));
            }
        }

        if (empty($ttlString)) {
             $this->messenger->addWarning($this->translate('Could not generate TTL data from the collecting items.'));
             return $this->redirect()->toRoute('admin/graphdb_data_sync/extract');
        }

        // 5. Enviar TTL para o GraphDB
        $success = $this->sendTtlToGraphDb($ttlString, $graphDbConfig);

        // 6. Mostrar Mensagem e Redirecionar
        if ($success) {
            $this->messenger->addSuccess($this->translate('Data successfully synchronized with GraphDB.'));
        } else {
            // Mensagem de erro já foi adicionada em sendTtlToGraphDb
        }
        // Redirecionar para a página de extração ou índice
        return $this->redirect()->toRoute('admin/graphdb_data_sync/extract');
    }

    // --- Função Auxiliar para Transformar em TTL ---
    /**
     * Transforma os dados dos collecting items em uma string TTL.
     * ATENÇÃO: O $propertyMap DEVE ser cuidadosamente preenchido pelo utilizador
     * para corresponder às suas propriedades Omeka e à ontologia alvo (ah:, crm:, etc.).
     *
     * @param array $collectingItems Array de collecting items da API Omeka S.
     * @return string String formatada em Turtle (TTL).
     */
    /**
     * Transforma os dados dos collecting items em uma string TTL.
     * ATENÇÃO: O $propertyMap DEVE ser cuidadosamente preenchido/verificado pelo utilizador
     * para corresponder às suas propriedades Omeka e à ontologia alvo (ah:, crm:, etc.).
     *
     * @param array $collectingItems Array de collecting items da API Omeka S.
     * @return string String formatada em Turtle (TTL).
     */
    private function transformToTtl(array $collectingItems): string
    {
        // 1. Definição dos Prefixos (mantidos da sua versão)
        $prefixes = [
            '@prefix ah: <http://www.purl.com/ah/ms/ahMS#> .' . PHP_EOL,
            '@prefix ah-shape: <http://www.purl.com/ah/kos/ah-shape/> .' . PHP_EOL,
            '@prefix ah-variant: <http://www.purl.com/ah/kos/ah-variant/> .' . PHP_EOL,
            '@prefix ah-base: <http://www.purl.com/ah/kos/ah-base/> .' . PHP_EOL,
            '@prefix ah-chippingMode: <http://www.purl.com/ah/kos/ah-chippingMode/> .' . PHP_EOL,
            '@prefix ah-chippingDirection: <http://www.purl.com/ah/kos/ah-chippingDirection/> .' . PHP_EOL,
            '@prefix ah-chippingDelineation: <http://www.purl.com/ah/kos/ah-chippingDelineation/> .' . PHP_EOL,
            '@prefix ah-chippingLocation: <http://www.purl.com/ah/kos/ah-chippingLocation/> .' . PHP_EOL,
            '@prefix ah-chippingShape: <http://www.purl.com/ah/kos/ah-chippingShape/> .' . PHP_EOL,
            '@prefix owl: <http://www.w3.org/2002/07/owl#> .' . PHP_EOL,
            '@prefix dct: <http://purl.org/dc/terms/> .' . PHP_EOL,
            '@prefix foaf: <http://xmlns.com/foaf/0.1/> .' . PHP_EOL,
            '@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .' . PHP_EOL,
            '@prefix schema: <http://schema.org/> .' . PHP_EOL,
            '@prefix dcterms: <http://purl.org/dc/terms/> .' . PHP_EOL,
            '@prefix voaf: <http://purl.org/vocommons/voaf#> .' . PHP_EOL,
            '@prefix skos: <http://www.w3.org/2004/02/skos/core#> .' . PHP_EOL,
            '@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .' . PHP_EOL,
            '@prefix vann: <http://purl.org/vocab/vann/> .' . PHP_EOL,
            '@prefix dbo: <http://dbpedia.org/ontology/> .' . PHP_EOL,
            '@prefix time: <http://www.w3.org/2006/time# .' . PHP_EOL,
            '@prefix edm: <http://www.europeana.eu/schemas/edm#> .' . PHP_EOL,
            '@prefix crm: <http://www.cidoc-crm.org/cidoc-crm/> .' . PHP_EOL,
            '@prefix crmsci: <http://cidoc-crm.org/extensions/crmsci/> .' . PHP_EOL,
            '@prefix crmarchaeo: <http://www.cidoc-crm.org/extensions/crmarchaeo/> .' . PHP_EOL,
            '@prefix geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .' . PHP_EOL,
            '@prefix wdrs: <http://www.w3.org/2007/05/powder-s#> .' . PHP_EOL,
            '@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .' . PHP_EOL,
            '@prefix sh: <http://www.w3.org/ns/shacl#> .' . PHP_EOL,
            '@prefix aat: <http://vocab.getty.edu/aat/> .' . PHP_EOL,
            '@prefix excav: <https://purl.org/ah/ms/excavationMS#> .' . PHP_EOL,
        ];
        $ttl = implode('', $prefixes);
        $ttl .= PHP_EOL;

        // 2. *** MAPEAMENTO DETALHADO - AJUSTADO COM BASE NAS VIOLAÇÕES SHACL ***
        $propertyMap = [
            // Omeka Prop ID => [ 'predicate' => Predicado RDF, 'type' => tipo, detalhes...]
            '10' => ['predicate' => 'dcterms:identifier', 'type' => 'literal', 'literal_datatype' => 'xsd:string'], // FIX: SHACL exige xsd:string
            '57' => ['predicate' => 'ah:hasAnnotation', 'type' => 'literal', 'literal_datatype' => 'xsd:string'], // Manteve-se, ajustar predicado se necessário
            '476' => ['predicate' => 'ah:conditionState', 'type' => 'boolean', // FIX: Garantir que gera ^^xsd:boolean
                      'value_map' => ['True (Complete)' => true, 'False (False)' => false, 'True (Complete' => true, 'False (Broken)' => false]],
            '399' => ['predicate' => 'ah:bodyType', 'type' => 'boolean', // FIX: Garantir que gera ^^xsd:boolean (ah:body no SHACL?)
                      'value_map' => ['True (Elongate)' => true, 'False (Short)' => false]],
            '7461' => ['predicate' => 'ah:variant', 'type' => 'iri', 'iri_prefix' => 'ah-variant:',
                       'value_map' => ['Flat' => 'flat', 'Raised' => 'raised', 'Thick' => 'thick']], // OK
            '7460' => ['predicate' => 'ah:shape', 'type' => 'iri', 'iri_prefix' => 'ah-shape:',
                       'value_map' => ['Triangle' => 'triangle', 'Losangular' => 'losangular', 'Pedunculated' => 'pedunculated']], // OK
            '257' => ['predicate' => 'geo:lat', 'type' => 'literal', 'literal_datatype' => 'xsd:decimal'], // OK
            '260' => ['predicate' => 'geo:long', 'type' => 'literal', 'literal_datatype' => 'xsd:decimal'], // OK (revisar se é mesmo longitude)
            '7462' => ['predicate' => 'ah:point', 'type' => 'boolean', // FIX: Garantir que gera ^^xsd:boolean
                       'value_map' => ['True (Sharp)' => true, 'False (Fractured)' => false]],
            '7463' => ['predicate' => 'ah:bodySymmetry', 'type' => 'boolean', // FIX: Garantir que gera ^^xsd:boolean (predicado não está no SHACL)
                       'value_map' => ['True (Symmetrical)' => true, 'False (Non-symmetrical)' => false]],
            '7464' => ['predicate' => 'ah:base', 'type' => 'iri', 'iri_prefix' => 'ah-base:',
                       'value_map' => ['Straight' => 'straight', 'Convex' => 'convex', 'Concave' => 'concave', 'Pedunculated' => 'pedunculated', 'Triangular' => 'triangular']], // OK
            // Propriedade 474 - PROBLEMÁTICA. Mapeando para crm:P43_has_dimension como literal decimal. REVISAR.
             '474' => ['predicate' => 'crm:P43_has_dimension', 'type' => 'literal', 'literal_datatype' => 'xsd:decimal'],
            '7465' => ['predicate' => 'ah:mode', 'type' => 'iri', 'iri_prefix' => 'ah-chippingMode:',
                       'value_map' => ['Plane' => 'plane', 'Parallel' => 'parallel', 'Sub-Parallel' => 'sub-parallel']], // OK
            '7466' => ['predicate' => 'ah:amplitude', 'type' => 'boolean', // FIX: Garantir que gera ^^xsd:boolean
                       'value_map' => ['True (Marginal)' => true, 'False (Deep)' => false]],
            '7467' => ['predicate' => 'ah:direction', 'type' => 'iri', 'iri_prefix' => 'ah-chippingDirection:',
                       'value_map' => ['Direct' => 'direct', 'Reverse' => 'reverse', 'Bifacial' => 'bifacial']], // OK
            '7468' => ['predicate' => 'ah:orientation', 'type' => 'boolean', // FIX: Garantir que gera ^^xsd:boolean
                       'value_map' => ['True (Lateral)' => true, 'False (Transverse)' => false]],
            '7469' => ['predicate' => 'ah:delineation', 'type' => 'iri', 'iri_prefix' => 'ah-chippingDelineation:',
                       'value_map' => ['Continuous' => 'continuous', 'Composite' => 'composite', 'Denticulated' => 'denticulated']], // OK
            '7470' => ['predicate' => 'ah:chippinglocation-Side', 'type' => 'iri', 'iri_prefix' => 'ah-chippingLocation:',
                       'value_map' => ['Distal' => 'distal', 'Median' => 'median', 'Proximal' => 'proximal']], // OK (gera múltiplos)
            '7471' => ['predicate' => 'ah:chippinglocation-Transversal', 'type' => 'iri', 'iri_prefix' => 'ah-chippingLocation:',
                       'value_map' => ['Distal' => 'distal', 'Median' => 'median', 'Proximal' => 'proximal']], // OK (gera múltiplos)
            '7472' => ['predicate' => 'ah:chippingShape', 'type' => 'iri', 'iri_prefix' => 'ah-chippingShape:',
                       'value_map' => ['Straight' => 'straight', 'Convex' => 'convex', 'Concave' => 'concave', 'Sinuous' => 'sinuous']], // OK
            '478' => ['predicate' => 'crm:P45_consists_of', 'type' => 'iri', 'iri_prefix' => 'aat:', // FIX: SHACL exige IRI. Usando AAT.
                      'value_map' => ['Gold' => '300010909', 'Flint' => '300010357' /* Adicionar outros materiais */ ]], // Mapeia "Gold" para o ID AAT 300010909

            // Propriedade em falta no SHACL: crm:P12i_was_present_at (minCount 1)
            // Você precisa de uma propriedade Omeka que guarde o evento/localização
            // e mapeá-la aqui. Exemplo:
            // 'ID_DA_SUA_PROPRIEDADE_OMEKA_PARA_EVENTO' => ['predicate' => 'crm:P12i_was_present_at', 'type' => 'iri'],
        ];

        // 3. Loop Principal e Geração de Triplos
        foreach ($collectingItems as $cItem) {
            $itemUri = $cItem['o:item']['@id'] ?? null;
            if (!$itemUri) {
                 error_log("Collecting item ID " . ($cItem['o:id'] ?? '??') . " sem Omeka item associado.");
                 continue;
            }
            $itemSubject = '<' . $itemUri . '>'; // Usar URI completo

            $ttl .= PHP_EOL;
            $ttl .= '# TTL para Item: ' . $itemUri . PHP_EOL;
            $ttl .= $itemSubject . ' a crm:E24_Physical_Man-Made_Thing .' . PHP_EOL;

            $generatedTriples = []; // Para evitar duplicados e armazenar triplos do item atual

            if (isset($cItem['o-module-collecting:input']) && is_array($cItem['o-module-collecting:input'])) {
                foreach ($cItem['o-module-collecting:input'] as $input) {
                    $propId = $input['o-module-collecting:prompt']['o:property']['o:id'] ?? null;
                    $value = $input['o-module-collecting:text'] ?? null;

                    if ($propId === null || $value === null || $value === '' || !isset($propertyMap[(string)$propId])) {
                         if($propId !== null && $value !== '' && !isset($propertyMap[(string)$propId])) {
                              error_log("Propriedade Omeka ID " . $propId . " não mapeada em \$propertyMap. Ignorando valor: '" . $value . "' para item " . $itemUri);
                         }
                         continue;
                    }

                    $mapping = $propertyMap[(string)$propId];
                    $predicate = $mapping['predicate'];
                    $object = '';

                    try {
                        switch ($mapping['type']) {
                            case 'literal':
                                $datatype = $mapping['literal_datatype'] ?? null; // Pega o datatype do mapa
                                $object = $this->formatTtlLiteral($value, null, $datatype); // Passa o datatype para o formatador
                                break;

                            case 'boolean':
                                $boolValue = null;
                                if (isset($mapping['value_map']) && array_key_exists($value, $mapping['value_map'])) {
                                    $boolValue = $mapping['value_map'][$value] ? 'true' : 'false';
                                } else {
                                    // Fallback simples melhorado
                                    $lowerVal = strtolower(trim($value));
                                    $trueSynonyms = ['true', '1', 'complete', 'marginal', 'symmetrical', 'sharp', 'yes', 'sim', 'verdadeiro'];
                                    $falseSynonyms = ['false', '0', 'broken', 'deep', 'non-symmetrical', 'fractured', 'no', 'nao', 'não', 'falso'];
                                    if (in_array($lowerVal, $trueSynonyms) || strpos($lowerVal, 'true') === 0) $boolValue = 'true';
                                    elseif (in_array($lowerVal, $falseSynonyms) || strpos($lowerVal, 'false') === 0) $boolValue = 'false';
                                }

                                if ($boolValue !== null) {
                                    // Passa 'xsd:boolean' explicitamente para o formatador
                                    $object = $this->formatTtlLiteral($boolValue, null, 'xsd:boolean');
                                } else {
                                    error_log("Não foi possível mapear valor booleano para a propriedade Omeka ID $propId: '$value' para item " . $itemUri);
                                }
                                break;

                            case 'iri':
                                $iriSuffix = null;
                                if (isset($mapping['value_map']) && array_key_exists($value, $mapping['value_map'])) {
                                    $iriSuffix = $mapping['value_map'][$value];
                                } else {
                                     $cleanedValue = trim(strtolower($value));
                                     $cleanedValue = preg_replace('/\s+/', '_', $cleanedValue);
                                     $cleanedValue = preg_replace('/[^a-z0-9_-]/', '', $cleanedValue);
                                     if ($cleanedValue !== '') {
                                          $iriSuffix = $cleanedValue;
                                          error_log("Usando fallback de sufixo IRI para prop Omeka ID $propId: '$value' -> '$iriSuffix' para item " . $itemUri);
                                     }
                                }

                                if (!empty($iriSuffix) && isset($mapping['iri_prefix'])) {
                                    $object = $mapping['iri_prefix'] . $iriSuffix; // Ex: aat:12345 ou ah-shape:triangle

                                    // FIX Adicional: Declarar o tipo do recurso material se for o caso
                                    if ($predicate === 'crm:P45_consists_of') {
                                        // Adiciona o tipo para o material IRI
                                        $materialTriple = $object . ' a crm:E57_Material .' . PHP_EOL;
                                        if (!in_array($materialTriple, $generatedTriples)) { // Evita duplicar a declaração do tipo
                                             $generatedTriples[] = $materialTriple;
                                        }
                                    }
                                } else {
                                    error_log("Não foi possível construir IRI para a propriedade Omeka ID $propId: valor '$value' para item " . $itemUri);
                                }
                                break;

                            default:
                                $object = $this->formatTtlLiteral($value);
                                error_log("Tipo de mapeamento desconhecido '{$mapping['type']}' para prop Omeka ID $propId. Tratando como literal para item " . $itemUri);
                                break;
                        }
                    } catch (\Exception $e) {
                         error_log("Erro ao processar propriedade Omeka ID $propId com valor '$value' para item " . $itemUri . ": " . $e->getMessage());
                         continue;
                    }

                    // Adiciona o triplo principal se o objeto foi gerado com sucesso
                    if (!empty($object)) {
                         $triple = $itemSubject . ' ' . $predicate . ' ' . $object . ' .' . PHP_EOL;
                         if (!in_array($triple, $generatedTriples)) { // Evita triplos idênticos exatos (útil para props repetidas com mesmo valor)
                             $generatedTriples[] = $triple;
                         }
                    }
                } // Fim loop $input
            } // Fim if isset $input

             // Adiciona os triplos gerados para este item ao TTL final
             $ttl .= implode('', $generatedTriples);

        } // Fim loop $collectingItems

        return $ttl;
    }


    // --- Função Auxiliar para Formatar Literais TTL (ATUALIZADA) ---
    /**
     * Formata um valor PHP como um literal RDF Turtle.
     *
     * @param mixed $value O valor a formatar.
     * @param string|null $lang O código do idioma (ex: "en", "pt").
     * @param string|null $datatype O datatype (ex: "xsd:string", "xsd:integer", "<http://example.com/MyType>")
     * @return string O literal formatado em Turtle.
     */
    private function formatTtlLiteral($value, ?string $lang = null, ?string $datatype = null): string
    {
        // Escapa caracteres especiais: ", \, newline (\n), carriage return (\r)
        // Nota: addcslashes escapa muitas coisas, talvez só precise escapar " e \
        // $escapedValue = str_replace(['\\', '"'], ['\\\\', '\\"'], (string)$value); // Alternativa mais simples
        $escapedValue = addcslashes((string)$value, "\"\\\n\r");

        $suffix = '';

        // 1. Prioridade para Datatype explícito
        if ($datatype) {
            // Se for um datatype XSD comum, usa o prefixo. Senão, URI completo.
            if (strpos($datatype, 'xsd:') === 0) {
                 $suffix = '^^' . $datatype;
            } elseif ($datatype === 'rdf:langString' && $lang){ // Trata rdf:langString se o idioma for fornecido
                 $suffix = '@' . $lang;
            } else {
                 // Assume URI completo para outros datatypes
                 $suffix = '^^<' . trim($datatype, '<>') . '>'; // Garante que não tem <> extras
            }
        // 2. Se não houver datatype, mas houver idioma, usa a tag de idioma
        } elseif ($lang) {
            $suffix = '@' . $lang;
        // 3. Se não houver datatype nem idioma, tenta detetar automaticamente (apenas se nenhum foi dado)
        } else {
             // Mantém a deteção automática de numéricos
             if (is_numeric($value)) {
                 if (strpos((string)$value, '.') === false && stripos((string)$value, 'e') === false) {
                    $suffix = '^^xsd:integer'; // Inteiro
                 } else {
                    $suffix = '^^xsd:decimal'; // Decimal ou float
                 }
             }
             // Poderia adicionar aqui deteção automática de booleanos se necessário,
             // mas é melhor definir explicitamente no $propertyMap.
             // else if (strtolower((string)$value) === 'true' || strtolower((string)$value) === 'false') {
             //    $suffix = '^^xsd:boolean';
             // }

             // Pode-se optar por adicionar ^^xsd:string por defeito se nenhum outro se aplicar
             // else {
             //    $suffix = '^^xsd:string';
             // }
        }

        // Construir o literal final
        return '"' . $escapedValue . '"' . $suffix;
    }


    // --- Função Auxiliar para Enviar TTL ao GraphDB ---
    private function sendTtlToGraphDb(string $ttlData, array $config): bool
    {
        $endpoint = rtrim($config['graphdb_endpoint'], '/') . '/statements'; // Endpoint comum para carregar dados
        $username = $config['graphdb_username'] ?? null;
        $password = $config['graphdb_password'] ?? null;

        try {
            $this->httpClient->resetParameters(true); // Limpa parâmetros de pedidos anteriores
            $this->httpClient->setUri($endpoint);
            $this->httpClient->setMethod(Request::METHOD_POST);
            $this->httpClient->setRawBody($ttlData); // Define o corpo com os dados TTL

            // Define cabeçalhos
            $headers = $this->httpClient->getRequest()->getHeaders();
            $headers->addHeaderLine('Content-Type', 'text/turtle');
            $headers->addHeaderLine('Accept', 'application/json'); // Aceita JSON como resposta do GraphDB

            // Adiciona Autenticação Básica se configurada
            if ($username && $password !== null) { // Verifica se username está definido e password não é null
                $this->httpClient->setAuth($username, $password, Client::AUTH_BASIC);
            }

            $response = $this->httpClient->send();

            if ($response->isSuccess()) {
                return true;
            } else {
                $this->messenger->addError(
                    $this->translate('Failed to send data to GraphDB. Status: %s - Response: %s'),
                    $response->getStatusCode(),
                    $response->getBody()
                );
                 error_log('GraphDB Sync Error: Status ' . $response->getStatusCode() . ' - Body: ' . $response->getBody()); // Log
                return false;
            }
        } catch (\Exception $e) {
            $this->messenger->addError($this->translate('Error connecting to GraphDB: %s'), $e->getMessage());
             error_log('GraphDB Connection Exception: ' . $e->getMessage()); // Log
            return false;
        }
    }

     // --- Função Auxiliar para obter URL base da API ---
     private function getBaseApiUrl() {
          // Obtem o URL base do Omeka S (ex: http://localhost/)
          // Atenção: Isto pode precisar de ajustes dependendo da configuração do servidor
          $uri = $this->getRequest()->getUri();
          $scheme = $uri->getScheme() ?: 'http';
          $host = $uri->getHost() ?: 'localhost';
          $port = $uri->getPort() ? ':' . $uri->getPort() : '';
          $basePath = rtrim(dirname($this->getRequest()->getBasePath()), '/\\'); // Pode precisar de ajustes
          return sprintf('%s://%s%s%s/api/', $scheme, $host, $port, $basePath);
     }

    private function getOmekaItems()
    {
        $omekaItems = [];
        
        try {
            // Use the controller's built-in url() helper
            $apiUrl = 'http://localhost/api/collecting_items';
            
            $this->httpClient->setUri($apiUrl);
            $this->httpClient->setMethod('GET');
            $response = $this->httpClient->send();
    
            if ($response->isSuccess()) {
                $omekaItems = Json::decode($response->getBody(), Json::TYPE_ARRAY);
            } else {
                $this->messenger->addError('API request failed with status: ' . $response->getStatusCode());
            }
        } catch (\Exception $e) {
            $this->messenger->addError('API connection error: ' . $e->getMessage());
        }
    
        return $omekaItems;
    }

    private function raise403($message)
    {
        $this->plugin('messenger')->addError($message);
        return $this->redirect()->toRoute('admin');
    }
}
