<?php
namespace Omeka\Form;

class ModuleStateChangeForm extends AbstractForm
{
    protected $options = ['module_action' => null, 'module_id' => null];

    public function buildForm()
    {
        $url = $this->getViewHelper('Url');

        switch ($this->getOption('module_action')) {
            case 'install':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'install'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Install'; // @translate
                $class = 'o-icon-install';
                break;
            case 'activate':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'activate'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Activate'; // @translate
                $class = 'o-icon-activate';
                break;
            case 'deactivate':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'deactivate'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Deactivate'; // @translate
                $class = 'o-icon-deactivate';
                break;
            case 'upgrade':
                $action = $url(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'upgrade'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Upgrade'; // @translate
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
                'title' => $label,
                'class' => $class,
            ],
        ]);
    }
}
