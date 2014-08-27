<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\UserForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'user',
            'action' => 'browse',
        ));
    }

    public function addAction()
    {
        $view = new ViewModel;
        if ($this->getRequest()->isPost()) {
            $response = $this->api()->create('users', $this->params()->fromPost());
            if ($response->isError()) {
                $view->setVariable('errors', $response->getErrors());
            } else {
                $view->setVariable('user', $response->getContent());
            }
        }
        return $view;
    }

    public function browseAction()
    {
        $view = new ViewModel;
        $response = $this->api()->search('users', array());
        if ($response->isError()) {
            $view->setVariable('errors', $response->getErrors());
        } else {
            $view->setVariable('users', $response->getContent());
        }
        return $view;
    }

    public function showAction()
    {
        $view = new ViewModel;
        $form = new UserForm;
        $id = $this->params('id');

        $readResponse = $this->api()->read('users', $id);
        if ($this->apiError($readResponse, $view)) {
            return $view;
        }
        $user = $readResponse->getContent();
        $data = $user->jsonSerialize();
        $form->setData(array(
            'username' => $data['o:username'],
            'name' => $data['o:name'],
            'email' => $data['o:email']
        ));

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $data = array(
                    'o:username' => $formData['username'],
                    'o:name' => $formData['name'],
                    'o:email' => $formData['email'],
                );
                $response = $this->api()->update('users', $id, $data);
                if (!$this->apiError($response, $view)) {
                    $this->messenger()->addSuccess('User updated.');
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('user', $user);
        $view->setVariable('form', $form);
        return $view;
    }
}
