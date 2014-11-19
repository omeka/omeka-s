<?php 
namespace Omeka\Controller\Admin;

use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyImportForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'vocabulary',
            'action' => 'browse',
        ));
    }

    public function showAction()
    {
        $view = new ViewModel;
        $id = $this->params('id');
        $response = $this->api()->read('vocabularies', $id);
        $view->setVariable('vocabulary', $response->getContent());
        return $view;
    }


    public function browseAction()
    {
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('vocabularies', $query);
        $view->setVariable('vocabularies', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $response = $this->api()->read(
            'vocabularies', array('id' => $this->params('id'))
        );
        $view->setVariable('vocabulary', $response->getContent());
        return $view;
    }

    public function importAction()
    {
        $view = new ViewModel;
        $form = new VocabularyImportForm($this->getServiceLocator());

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $importer = $this->getServiceLocator()->get('Omeka\RdfImporter');
                try {
                    $response = $importer->import(
                        'file', $data, array('file' => $data['file']['tmp_name'])
                    );
                    if ($response->isError()) {
                        $form->setMessages($response->getErrors());
                    } else {
                        $this->messenger()->addSuccess('The vocabulary was successfully imported.');
                        return $this->redirect()->toUrl($response->getContent()->url());
                    }
                } catch (\Exception $e) {
                    $this->messenger()->addError($e->getMessage());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('form', $form);
        return $view;
    }
    
    public function editAction()
    {
        $view = new ViewModel;
        $form = new VocabularyForm($this->getServiceLocator());
        $id = $this->params('id');

        $readResponse = $this->api()->read('vocabularies', $id);
        $vocabulary = $readResponse->getContent();
        $data = $vocabulary->jsonSerialize();
        $form->setData($data);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api()->update('vocabularies', $id, $formData);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Vocabulary updated.');
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('form', $form);
        return $view;
    }

    public function editCustomAction()
    {
        $view = new ViewModel;
        return $view;
    }
    
    public function addResourceTemplateAction()
    {
        $view = new ViewModel;
        return $view;
    }
}
