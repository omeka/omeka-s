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

    private $config; // Add this property

    private $urlHelper;

    public function __construct(
        Acl $acl,
        Messenger $messenger,
        Client $httpClient,
        PluginManager $pluginManager,
        FormElementManager $formElementManager,
        $urlHelper // Add this
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
                    'graphdb_endpoint' => $data['graphdb_endpoint'],
                    'graphdb_username' => $data['graphdb_username'],
                    'graphdb_password' => $data['graphdb_password'],
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
        $configFile = OMEKA_PATH . '/config/graphdb_sync.ini';
        if (file_exists($configFile)) {
            $config = $reader->fromFile($configFile);
        }
        return $config['graphdb_sync'] ?? [];
    }

    private function getOmekaItems()
    {
        $omekaItems = [];
        
        try {
            // Use the controller's built-in url() helper
            $apiUrl = $this->url()->fromRoute('api/default', [
                'resource' => 'items'
            ], ['force_canonical' => true]);
            
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
