<?php
namespace Omeka\Form;

class ModuleStateChangeForm extends AbstractForm
{
    protected $options = array('module_action' => null, 'module_id' => null);

    public function buildForm()
    {
        $translator = $this->getTranslator();
        $url = $this->getViewHelper('Url');

        switch ($this->getOption('module_action')) {
            case 'install':
                $action = $url(
                    'admin/default',
                    array('controller' => 'module', 'action' => 'install'),
                    array('query' => array('id' => $this->getOption('module_id')))
                );
                $label = $translator->translate('Install');
                $title = $translator->translate('Install');
                $class = 'o-icon-install';
                break;
            case 'activate':
                $action = $url(
                    'admin/default',
                    array('controller' => 'module', 'action' => 'activate'),
                    array('query' => array('id' => $this->getOption('module_id')))
                );
                $label = $translator->translate('Activate');
                $title = $translator->translate('Activate');
                $class = 'o-icon-activate';
                break;
            case 'deactivate':
                $action = $url(
                    'admin/default',
                    array('controller' => 'module', 'action' => 'deactivate'),
                    array('query' => array('id' => $this->getOption('module_id')))
                );
                $label = $translator->translate('Deactivate');
                $title = $translator->translate('Deactivate');
                $class = 'o-icon-deactivate';
                break;
            case 'upgrade':
                $action = $url(
                    'admin/default',
                    array('controller' => 'module', 'action' => 'upgrade'),
                    array('query' => array('id' => $this->getOption('module_id')))
                );
                $label = $translator->translate('Upgrade');
                $title = $translator->translate('Upgrade');
                $class = 'o-icon-upgrade';
                break;
            default:
                break;
        }

        $this->setAttribute('action', $action);

        $this->add(array(
            'type' => 'button',
            'name' => 'id',
            'options' => array(
                'label' => $label,
            ),
            'attributes' => array(
                'type' => 'submit',
                'title' => $title,
                'class' => $class,
            ),
        ));
    }
}
