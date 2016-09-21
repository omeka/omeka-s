<?php
namespace Omeka\Controller\Admin;

use Omeka\Event\Event;
use Omeka\Form\SettingForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class SettingController extends AbstractActionController
{
    public function browseAction()
    {
        $form = $this->getForm(SettingForm::class);

        $event = new Event(Event::GLOBAL_SETTINGS_FORM, $this, ['form' => $form]);
        $this->getEventManager()->triggerEvent($event);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $fieldsets = $form->getFieldsets();
                unset($data['csrf']);
                foreach ($data as $id => $value) {
                    if (array_key_exists($id, $fieldsets) && is_array($value)) {
                        // De-nest fieldsets.
                        foreach ($value as $fieldsetId => $fieldsetValue) {
                            $this->settings()->set($fieldsetId, $fieldsetValue);
                        }
                    } else {
                        $this->settings()->set($id, $value);
                    }
                }
                $this->messenger()->addSuccess('Settings successfully updated'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            } else {
                $this->messenger()->addErrors($form->getMessages());
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}
