<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\SettingForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SettingController extends AbstractActionController
{
    public function browseAction()
    {
        $form = $this->getForm(SettingForm::class);
        $data = [
            'administrator_email' => $this->settings()->get('administrator_email'),
            'installation_title' => $this->settings()->get('installation_title'),
            'time_zone' => $this->settings()->get('time_zone'),
            'pagination_per_page' => $this->settings()->get('pagination_per_page'),
            'property_label_information' => $this->settings()->get('property_label_information'),
            'use_htmlpurifier' => $this->settings()->get('use_htmlpurifier'),
            'default_site' => $this->settings()->get('default_site')
        ];
        $form->setData($data);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                foreach ($form->getData() as $key => $value) {
                    // Set whitelisted settings only, otherwise this would set
                    // the CSRF value and any other element passed by the form.
                    if (array_key_exists($key, $data)) {
                        $this->settings()->set($key, $value);
                    }
                }
                $this->messenger()->addSuccess('Settings updated');
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}
