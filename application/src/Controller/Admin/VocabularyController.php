<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyUpdateForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

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
        $this->browse()->setDefaults('vocabularies');
        $response = $this->api()->search('vocabularies', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

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
        $view->setVariable('resourceLabel', 'vocabulary'); // @translate
        $view->setVariable('partialPath', 'omeka/admin/vocabulary/show-details');
        return $view;
    }

    public function importAction()
    {
        $form = $this->getForm(VocabularyForm::class);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $strategy = null;
                $options = [
                    'format' => $data['vocabulary-file']['format'],
                    'lang' => $data['vocabulary-advanced']['lang'],
                    'label_property' => $data['vocabulary-advanced']['label_property'],
                    'comment_property' => $data['vocabulary-advanced']['comment_property'],
                ];
                if ('upload' === $data['vocabulary-file']['import_type']) {
                    $strategy = 'file';
                    $options['file'] = $data['vocabulary-file']['file']['tmp_name'];
                } elseif ('url' === $data['vocabulary-file']['import_type']) {
                    $strategy = 'url';
                    $options['url'] = $data['vocabulary-file']['url'];
                }
                try {
                    $response = $this->rdfImporter->import($strategy, $data['vocabulary-info'], $options);
                    $message = new Message(
                        'Vocabulary successfully imported. %s', // @translate
                        sprintf('<a href="%s">%s</a>', htmlspecialchars($this->url()->fromRoute(null, [], true)), $this->translate('Import another vocabulary?'))
                    );
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                } catch (\Exception $e) {
                    $messages = [];
                    if ($e->getMessage()) {
                        $messages[] = $e->getMessage();
                    }
                    // Messages may be thrown from the API via the importer.
                    if (method_exists($e, 'getErrorStore')) {
                        foreach ($e->getErrorStore()->getErrors() as $message) {
                            $messages[] = $message;
                        }
                    }
                    $this->messenger()->addErrors($messages);
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
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary]);
        if ($vocabulary->isPermanent()) {
            throw new Exception\PermissionDeniedException('Cannot edit a permanent vocabulary');
        }
        $data = [
            'vocabulary-info' => [
                'o:label' => $vocabulary->label(),
                'o:comment' => $vocabulary->comment(),
                'o:namespace_uri' => $vocabulary->namespaceUri(),
            ],
        ];
        $form->setData($data);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->update('vocabularies', $this->params('id'), $data['vocabulary-info'], [], ['isPartial' => true]);
                $strategy = null;
                $options = [
                    'format' => $data['vocabulary-file']['format'],
                    'lang' => $data['vocabulary-advanced']['lang'],
                    'label_property' => $data['vocabulary-advanced']['label_property'],
                    'comment_property' => $data['vocabulary-advanced']['comment_property'],
                ];
                if ('upload' === $data['vocabulary-file']['import_type']) {
                    $strategy = 'file';
                    $options['file'] = $data['vocabulary-file']['file']['tmp_name'];
                } elseif ('url' === $data['vocabulary-file']['import_type']) {
                    $strategy = 'url';
                    $options['url'] = $data['vocabulary-file']['url'];
                }
                if (null === $strategy) {
                    $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                }
                try {
                    $diff = $this->rdfImporter->getDiff($strategy, $vocabulary->namespaceUri(), $options);
                    $this->messenger()->addSuccess('Please review these changes before you accept them.'); // @translate
                    $form = $this->getForm(VocabularyUpdateForm::class);
                    $form->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'update'], true));
                    $form->get('diff')->setValue(json_encode($diff));
                    $view->setVariable('diff', $diff);
                    $view->setTemplate('omeka/admin/vocabulary/update');
                } catch (\Exception $e) {
                    $this->messenger()->addError($e->getMessage());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    public function updateAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form = $this->getForm(VocabularyUpdateForm::class);
            $form->setData($data);
            if ($form->isValid()) {
                $this->rdfImporter->update($this->params('id'), json_decode($data['diff'], true));
                $this->messenger()->addSuccess('Changes to the vocabulary successfully made'); // @translate
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('vocabularies', $this->params('id'));
                if ($response) {
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

        $this->browse()->setDefaults('properties');
        $this->getRequest()->getQuery()->set('vocabulary_id', $this->params('id'));
        $propResponse = $this->api()->search('properties', $this->params()->fromQuery());
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));
        $this->paginator($propResponse->getTotalResults());

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

        $this->browse()->setDefaults('resource_classes');
        $this->getRequest()->getQuery()->set('vocabulary_id', $this->params('id'));
        $classResponse = $this->api()->search('resource_classes', $this->params()->fromQuery());
        $vocabResponse = $this->api()->read('vocabularies', $this->params('id'));
        $this->paginator($classResponse->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('resourceClasses', $classResponse->getContent());
        $view->setVariable('vocabulary', $vocabResponse->getContent());
        return $view;
    }
}
