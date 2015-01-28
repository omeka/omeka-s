<?php 
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyImportForm;
use Omeka\Mvc\Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }

    public function browseAction()
    {
        $view = new ViewModel;

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('vocabularies', $query);

        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('vocabularies', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $response = $this->api()->read('vocabularies', $this->params('id'));
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
                        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
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

        if ($vocabulary->isPermanent()) {
            throw new Exception\PermissionDeniedException('Cannot edit a permanent vocabulary');
        }

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
                    return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('form', $form);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('vocabularies', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Vocabulary could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Vocabulary successfully deleted');
                }
            } else {
                $this->messenger()->addError('Vocabulary could not be deleted');
            }
        }
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }

    public function propertiesAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array(
            'page' => $page,
            'vocabulary_id' => $this->params('id'),
        );
        $propResponse = $this->api()->search('properties', $query);
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));

        $view = new ViewModel;
        $this->paginator($propResponse->getTotalResults(), $page);
        $view->setVariable('properties', $propResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }

    public function classesAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array(
            'page' => $page,
            'vocabulary_id' => $this->params('id'),
        );
        $classResponse = $this->api()->search('resource_classes', $query);
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));

        $view = new ViewModel;
        $this->paginator($classResponse->getTotalResults(), $page);
        $view->setVariable('resourceClasses', $classResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }
}
