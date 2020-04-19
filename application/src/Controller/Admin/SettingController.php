<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\SettingForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SettingController extends AbstractActionController
{
    public function browseAction()
    {
        $form = $this->getForm(SettingForm::class);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $fieldsets = $form->getFieldsets();
                if ($data['general']['index_fulltext_search']) {
                    $this->jobDispatcher()->dispatch('Omeka\Job\IndexFulltextSearch');
                }
                unset($data['general']['index_fulltext_search']);
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
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}
