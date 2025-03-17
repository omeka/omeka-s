<?php
namespace Collecting\Mvc\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class CollectingCurrentForm extends AbstractPlugin
{
    public function __invoke()
    {
        $controller = $this->getController();
        return $controller->api()->read(
            'collecting_forms', $controller->params('form-id')
        )->getContent();
    }
}
