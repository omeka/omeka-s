<?php
namespace Omeka\Form;

use Zend\View\Helper\Url;
use Zend\Form\Form;

class ModuleStateChangeForm extends Form
{
    /**
     * @var array
     */
    protected $options = [
        'module_action' => null,
        'module_id' => null,
    ];

    /**
     * @var Url
     */
    protected $urlHelper;

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $urlHelper = $this->getUrlHelper();
        switch ($this->getOption('module_action')) {
            case 'install':
                $action = $urlHelper(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'install'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Install'; // @translate
                $class = 'o-icon-install green button';
                break;
            case 'activate':
                $action = $urlHelper(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'activate'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Activate'; // @translate
                $class = 'o-icon-activate';
                break;
            case 'deactivate':
                $action = $urlHelper(
                    'admin/default',
                    ['controller' => 'module', 'action' => 'deactivate'],
                    ['query' => ['id' => $this->getOption('module_id')]]
                );
                $label = 'Deactivate'; // @translate
                $class = 'o-icon-deactivate';
                break;
            case 'upgrade':
                $action = $urlHelper(
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

    /**
     * @param Url $urlHelper
     */
    public function setUrlHelper(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return Url
     */
    public function getUrlHelper()
    {
        return $this->urlHelper;
    }
}
