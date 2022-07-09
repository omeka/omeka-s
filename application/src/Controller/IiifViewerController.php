<?php
namespace Omeka\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IiifViewerController extends AbstractActionController
{
    public function indexAction()
    {
        // Set the default Mirador configuration.
        $miradorConfig = [
            'id' => 'viewer',
            'workspaceControlPanel' => [
                'enabled' => false,
            ],
            'window' => [
                'allowClose' => false,
                'allowFullscreen' => true,
                'allowMaximize' => false,
                'sideBarOpen' => true,
                'defaultSidebarPanelWidth' => 300,
            ],
            'osdConfig' => [
                'maxZoomPixelRatio' => 100,
            ],
            'windows' => [
                [
                    'manifestId' => $this->params()->fromQuery('url'),
                    'thumbnailNavigationPosition' => 'far-bottom',
                ],
            ],
        ];
        // Get the user-defined Mirador configuration and apply it to the
        // default configuration. Here we use an allow-list instead of a
        // recursive merge to prevent malicious configurations.
        $miradorConfigUser =  json_decode($this->params()->fromQuery('mirador_config'), true);
        if (isset($miradorConfigUser['window.sideBarOpen'])) {
            $miradorConfig['window']['sideBarOpen'] = $miradorConfigUser['window.sideBarOpen'];
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('miradorConfig', $miradorConfig);
        return $view;
    }
}
