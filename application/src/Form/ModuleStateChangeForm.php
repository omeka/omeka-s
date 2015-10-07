<?php
namespace Omeka\Form;

class ModuleStateChangeForm extends AbstractForm
{
    protected $options = ['module_action' => null, 'module_id' => null];

    public function buildForm()
    {
        $translator = $this->getTranslator();
        $url = $this->getViewHelper('Url');

        switch ($this->getOption('module_action')) {
            case 'install':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'install'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = $translator->translate('Install');
                $title = $translator->translate('Install');
                $class = 'o-icon-install';
                break;
            case 'activate':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'activate'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = $translator->translate('Activate');
                $title = $translator->translate('Activate');
                $class = 'o-icon-activate';
                break;
            case 'deactivate':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'deactivate'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = $translator->translate('Deactivate');
                $title = $translator->translate('Deactivate');
                $class = 'o-icon-deactivate';
                break;
            case 'upgrade':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'upgrade'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = $translator->translate('Upgrade');
                $title = $translator->translate('Upgrade');
                $class = 'o-icon-upgrade';
                break;
            default:
                break;
        }

        $this->setAttribute('action', $action);

        $this->add([
            'type' => 'button',
            'name' => 'id',
            'options' => [
                'label' => $label,
            ],
            'attributes' => [
                'type' => 'submit',
                'title' => $title,
                'class' => $class,
            ],
        ]);
    }
}
