<?php
use Zend\View\Model\ViewModel;

error_reporting(E_ALL);
if ((isset($_SERVER['APPLICATION_ENV'])
        && 'development' == $_SERVER['APPLICATION_ENV'])
    ||
    (isset($_SERVER['REDIRECT_APPLICATION_ENV'])
        && 'development' == $_SERVER['REDIRECT_APPLICATION_ENV'])
) {
    ini_set('display_errors', 1);
} else {
    ini_set('display_errors', 0);
}

require 'bootstrap.php';

try {
    $application = Omeka\Mvc\Application::init(require 'application/config/application.config.php');
    try {
        $application->run();
    } catch (\Exception $e) {
        $viewRenderer = $application->getServiceManager()->get('ViewRenderer');
        $model = new ViewModel;
        $model->setTemplate('error/index');
        $model->setVariable('exception', $e);
        $content = $viewRenderer->render($model);
        $parentModel = $application->getMvcEvent()->getViewModel();
        if (!$parentModel) {
            $parentModel = new ViewModel;
        }
        $parentModel->setTemplate('layout/layout');
        $parentModel->setVariable('content', $viewRenderer->render($model));
        http_response_code(500);
        error_log($e);
        echo $viewRenderer->render($parentModel);
    }
} catch (\Exception $e) {
    http_response_code(500);
    error_log($e);
    include OMEKA_PATH . '/application/view/error/fallback.phtml';
}
