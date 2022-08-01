<?php
namespace Omeka\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\MvcEvent;
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
            'workspace' => [
                'showZoomControls' => true,
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

        // Allow modules to modify the Mirador configuration.
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['mirador_config' => $miradorConfig]);
        $eventManager->triggerEvent(new MvcEvent('iiif_viewer.mirador_config', null, $args));
        $miradorConfig = $args['mirador_config'];

        // Apply user-defined configuration, which is set via the mirador_config
        // query parameter. Here we use an allow-list instead of a recursive
        // merge to prevent malicious configurations.
        $miradorConfigUser = json_decode((string) $this->params()->fromQuery('mirador_config'), true);
        if (isset($miradorConfigUser['window.sideBarOpen'])) {
            $miradorConfig['window']['sideBarOpen'] = $miradorConfigUser['window.sideBarOpen'];
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('miradorConfig', $miradorConfig);
        return $view;
    }
}
