<?php
namespace Omeka\Controller\Admin;

use PDO;
use Doctrine\DBAL\Connection;
use Omeka\Module;
use Omeka\Stdlib\Cli;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SystemInfoController extends AbstractActionController
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Cli
     */
    protected $cli;

    /**
     * @param Connection $connection
     * @param array $config
     * @param Cli $cli
     */
    public function __construct(Connection $connection, array $config, Cli $cli)
    {
        $this->connection = $connection;
        $this->config = $config;
        $this->cli = $cli;
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
            'Paths' => [
                'PHP' => sprintf(
                    '%s %s',
                    $this->getPhpPath(),
                    !$this->cli->validateCommand($this->getPhpPath()) ? $this->translate('[invalid]') : ''
                ),
                'ImageMagick' => sprintf(
                    '%s %s',
                    $this->getImagemagickPath(),
                    !$this->cli->validateCommand($this->getImagemagickPath()) ? $this->translate('[invalid]') : ''
                ),
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

    public function getPhpPath()
    {
        $phpPath = @$this->config['cli']['phpcli_path'];
        if (!$phpPath) {
            $phpPath = $this->cli->getCommandPath('php');
        }
        return $phpPath;
    }

    public function getImagemagickPath()
    {
        $imagemagickPath = @$this->config['thumbnails']['thumbnailer_options']['imagemagick_dir'];
        if (!$imagemagickPath) {
            $imagemagickPath = $this->cli->getCommandPath('convert');
        }
        return $imagemagickPath;
    }
}
