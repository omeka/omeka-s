<?php
namespace Omeka\Controller\Admin;

use PDO;
use Doctrine\DBAL\Connection;
use Omeka\Module;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SystemInfoController extends AbstractActionController
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function browseAction()
    {
        $model = new ViewModel;
        $model->setVariable('info', $this->getSystemInfo());
        return $model;
    }

    private function getSystemInfo()
    {
        $conn = $this->connection->getWrappedConnection();
        $mode = $this->connection->fetchColumn('SELECT @@sql_mode');

        $extensions = get_loaded_extensions();
        natcasesort($extensions);

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
                'Extensions' => $extensions,
            ],
            'MySQL' => [
                'Server Version' => $conn->getAttribute(PDO::ATTR_SERVER_VERSION),
                'Client Version' => $conn->getAttribute(PDO::ATTR_CLIENT_VERSION),
                'Mode' => explode(',', $mode),
            ],
            'OS' => [
                'Version' => sprintf('%s %s %s', php_uname('s'), php_uname('r'), php_uname('m')),
            ],
        ];

        $disabledFunctions = ini_get('disable_functions');
        if ($disabledFunctions) {
            $disabledFunctions = explode(',', $disabledFunctions);
            natcasesort($disabledFunctions);
            $info['PHP']['Disabled Functions'] = $disabledFunctions;
        }

        $disabledClasses = ini_get('disable_classes');
        if ($disabledClasses) {
            $disabledClasses = explode(',', $disabledClasses);
            natcasesort($disabledClasses);
            $info['PHP']['Disabled Classes'] = $disabledClasses;
        }

        return $info;
    }
}
