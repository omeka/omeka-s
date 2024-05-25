<?php
namespace Omeka\Controller\Admin;

use PDO;
use Doctrine\DBAL\Connection;
use Omeka\Module;
use Omeka\Module\Manager as Modules;
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
     * @var Modules
     */
    protected $modules;

    /**
     * @param Connection $connection
     * @param array $config
     * @param Cli $cli
     * @param Modules $moduleManager
     */
    public function __construct(Connection $connection, array $config, Cli $cli, Modules $modules)
    {
        $this->connection = $connection;
        $this->config = $config;
        $this->cli = $cli;
        $this->modules = $modules;
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
            'Modules' => [],
            'Free space' => [],
            'Paths' => [
                'PHP CLI path' => sprintf(
                    '%s %s',
                    $this->getPhpPath(),
                    !$this->cli->validateCommand($this->getPhpPath()) ? $this->translate('[invalid]') : ''
                ),
                'ImageMagick directory' => sprintf(
                    '%s %s',
                    $this->getImagemagickDir(),
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

        $moduleStates = [
            Modules::STATE_ACTIVE, Modules::STATE_NOT_ACTIVE, Modules::STATE_NOT_INSTALLED,
            Modules::STATE_NOT_FOUND, Modules::STATE_INVALID_MODULE, Modules::STATE_INVALID_INI,
            Modules::STATE_INVALID_OMEKA_VERSION, Modules::STATE_NEEDS_UPGRADE,
        ];
        foreach ($moduleStates as $moduleState) {
            $modules = $this->modules->getModulesByState($moduleState);
            if ($modules) {
                $info['Modules'][$moduleState] = array_map(function ($module) {
                    return sprintf('%s (%s)', $module->getName(), $module->getIni('version') ?? $module->getDb('version'));
                }, $modules);
            }
        }

        $freeSpaceSystem = disk_free_space('.');
        $info['Free space']['System'] = $this->formatSpace($freeSpaceSystem);
        $freeSpaceFilesDir = $this->getDirFiles();
        if ($freeSpaceFilesDir) {
            $freeSpaceFiles = disk_free_space($freeSpaceFilesDir);
            if ($freeSpaceFiles !== $freeSpaceSystem) {
                $info['Free space']['Local files'] = $this->formatSpace($freeSpaceFiles);
            }
            // Manage the case where directory "original" is mounted separately.
            $freeSpaceOriginalDir = $freeSpaceFilesDir . '/original';
            if (file_exists($freeSpaceOriginalDir)) {
                $freeSpaceOriginal = disk_free_space($freeSpaceOriginalDir);
                if ($freeSpaceFiles !== $freeSpaceOriginal) {
                    $info['Free space']['Local files (original)'] = $this->formatSpace($freeSpaceOriginal);
                }
            }
        }
        $freeSpaceTemp = disk_free_space($this->getDirTemp());
        if ($freeSpaceTemp !== $freeSpaceSystem) {
            $info['Free space']['Temp dir'] = $this->formatSpace($freeSpaceTemp);
        }

        return $info;
    }

    public function getPhpVersionAction()
    {
        $output = $this->cli->execute(sprintf('%s --version', $this->getPhpPath()));
        if (!$output) {
            $output = $this->translate('[Unable to execute command]');
        }
        $response = $this->getResponse();
        $response->setContent($output);
        return $response;
    }

    public function getImagemagickVersionAction()
    {
        $output = $this->cli->execute(sprintf('%s --version', $this->getImagemagickPath()));
        if (!$output) {
            $output = $this->translate('[Unable to execute command]');
        }
        $response = $this->getResponse();
        $response->setContent($output);
        return $response;
    }

    public function getPhpPath()
    {
        $phpPath = @$this->config['cli']['phpcli_path'];
        if (!$phpPath) {
            $phpPath = $this->cli->getCommandPath('php');
        }
        return $phpPath;
    }

    public function getImagemagickDir()
    {
        $imagemagickDir = @$this->config['thumbnails']['thumbnailer_options']['imagemagick_dir'];
        if (!$imagemagickDir) {
            $imagemagickDir = preg_replace('/convert$/', '', $this->cli->getCommandPath('convert'));
        }
        return $imagemagickDir;
    }

    public function getImagemagickPath()
    {
        return sprintf('%s/convert', $this->getImagemagickDir());
    }

    protected function getDirFiles(): ?string
    {
        $fileStore = $this->config['service_manager']['aliases']['Omeka\File\Store'];
        if ($fileStore === 'Omeka\File\Store\Local') {
            return $this->config['file_store']['local']['base_path'] ?: (OMEKA_PATH . '/files');
        }
        return null;
    }

    protected function getDirTemp(): ?string
    {
        return $this->config['temp_dir'] ?: sys_get_temp_dir();
    }

    protected function formatSpace($bytes): string
    {
        return sprintf('%1$.1f GiB', $bytes / (1024 * 1024 * 1024));
    }
}
