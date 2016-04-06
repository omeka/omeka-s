<?php
namespace Omeka\Controller\Admin;

use PDO;
use Omeka\Module;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SystemInfoController extends AbstractActionController
{
    public function browseAction()
    {
        $model = new ViewModel;
        $model->setVariable('info', $this->getSystemInfo());
        return $model;
    }

    private function getSystemInfo()
    {
        $conn = $this->getServiceLocator()->get('Omeka\Connection')->getWrappedConnection();
        $info = [
            'Omeka S' => [
                'Version' => Module::VERSION,
            ],
            'PHP' => [
                'Version' => phpversion(),
                'SAPI' => php_sapi_name(),
                'Memory Limit' => ini_get('memory_limit'),
                'POST Size Limit' => ini_get('post_max_size'),
                'File Upload Limit' => ini_get('upload_max_filesize'),
                'Garbage Collection' => gc_enabled(),
                'Extensions' => implode(', ', get_loaded_extensions()),
            ],
            'MySQL' => [
                'Server Version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
                'Client Version' => $conn->getAttribute(PDO::ATTR_CLIENT_VERSION),
            ],
            'OS' => [
                'Version' => sprintf('%s %s %s', php_uname('s'), php_uname('r'), php_uname('m'))
            ],
        ];

        $disabledFunctions = ini_get('disable_functions');
        if ($disabledFunctions) {
            $info['PHP Settings']['Disabled Functions'] = $disabledFunctions;
        }

        $disabledClasses = ini_get('disable_classes');
        if ($disabledClasses) {
            $info['PHP Settings']['Disabled Classes'] = $disabledClasses;
        }

        return $info;
    }
}
