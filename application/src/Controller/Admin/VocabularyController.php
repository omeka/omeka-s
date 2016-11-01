<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyImportForm;
use Omeka\Mvc\Exception;
use Omeka\Service\RdfImporter;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @param RdfImporter $rdfImporter
     */
    public function __construct(RdfImporter $rdfImporter)
    {
        $this->rdfImporter = $rdfImporter;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('label', 'asc');
        $response = $this->api()->search('vocabularies', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('vocabularies', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('vocabularies', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('vocabularies', $this->params('id'));
        $vocabulary = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $vocabulary);
        $view->setVariable('resourceLabel', 'vocabulary');
        $view->setVariable('partialPath', 'omeka/admin/vocabulary/show-details');
        return $view;
    }

    public function importAction()
    {
        $form = $this->getForm(VocabularyImportForm::class);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                try {
                    $response = $this->rdfImporter->import(
                        'file', $data, ['file' => $data['file']['tmp_name']]
                    );
                    $this->api($form)->detectError($response);
                    if ($response->isSuccess()) {
                        $message = new Message(
                            'Vocabulary successfully imported. %s', // @translate
                            sprintf(
                                '<a href="%s">%s</a>',
                                htmlspecialchars($this->url()->fromRoute(null, [], true)),
                                $this->translate('Import another vocabulary?')
                            ));
                        $message->setEscapeHtml(false);
                        $this->messenger()->addSuccess($message);
                        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                    }
                } catch (\Exception $e) {
                    $this->messenger()->addError($e->getMessage());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $form = $this->getForm(VocabularyForm::class);
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
                $response = $this->api($form)->update('vocabularies', $id, $formData);
                if ($response->isSuccess()) {
                    $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('form', $form);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('vocabularies', $this->params('id'));
                if ($response->isSuccess()) {
                    $this->messenger()->addSuccess('Vocabulary successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function propertiesAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $this->setBrowseDefaults('label', 'asc');
        $this->getRequest()->getQuery()->set('vocabulary_id', $this->params('id'));
        $propResponse = $this->api()->search('properties', $this->params()->fromQuery());
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));
        $this->paginator($propResponse->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('properties', $propResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }

    public function classesAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $this->setBrowseDefaults('label', 'asc');
        $this->getRequest()->getQuery()->set('vocabulary_id', $this->params('id'));
        $classResponse = $this->api()->search('resource_classes', $this->params()->fromQuery());
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));
        $this->paginator($classResponse->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('resourceClasses', $classResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }
}
