<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\SettingForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SettingController extends AbstractActionController
{
    public function browseAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $settings = $serviceLocator->get('Omeka\Settings');

        $form = new SettingForm($serviceLocator);
        $data = array(
            'administrator_email' => $settings->get('administrator_email'),
            'pagination_per_page' => $settings->get('pagination_per_page'),
            'property_label_information' => $settings->get('property_label_information'),
            'use_htmlpurifier' => $settings->get('use_htmlpurifier')
        );
        $form->setData($data);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                foreach ($formData as $key => $value) {
                    $settings->set($key, $value);
                }
                $this->messenger()->addSuccess('Settings updated');
                return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}
