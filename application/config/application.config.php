<?php
namespace Omeka;

$reader = new \Laminas\Config\Reader\Ini;

$url = getenv('OMEKA_DB_CONNECTION_URL');

try {
  $database = $reader->fromFile(OMEKA_PATH . '/config/database.ini');
} catch (\Laminas\Config\Exception\RuntimeException $e) {
  if (!$url) {
    throw $e;
  }
} finally {
  if ($url) {
    $database['url'] = $url;
  }
}

return [
    'modules' => [
        'Laminas\Form',
        'Laminas\I18n',
        'Laminas\Mvc\I18n',
        'Laminas\Mvc\Plugin\Identity',
        'Laminas\Navigation',
        'Laminas\Router',
        'Laminas\ZendFrameworkBridge',
        'Omeka',
    ],
    'module_listener_options' => [
        'module_paths' => [
            'Omeka' => OMEKA_PATH . '/application',
            OMEKA_PATH . '/modules',
        ],
        'config_glob_paths' => [
            OMEKA_PATH . '/config/local.config.php',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Omeka\Connection' => Service\ConnectionFactory::class,
            'Omeka\ModuleManager' => Service\ModuleManagerFactory::class,
            'Omeka\Status' => Service\StatusFactory::class,
        ],
    ],
    'connection' => $database,
];
