<?php
namespace Omeka\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\Property;
use Omeka\Entity\ResourceClass;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyImportForm;
use Omeka\Form\VocabularyUpdateForm;
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
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param RdfImporter $rdfImporter
     */
    public function __construct(RdfImporter $rdfImporter, EntityManager $entityManager)
    {
        $this->rdfImporter = $rdfImporter;
        $this->entityManager = $entityManager;
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
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();

        if ($vocabulary->isPermanent()) {
            throw new Exception\PermissionDeniedException('Cannot edit a permanent vocabulary');
        }

        $data = $vocabulary->jsonSerialize();
        $form->setData($data);

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $response = $this->api($form)->update('vocabularies', $this->params('id'), $data, [], true);
                if ($response->isSuccess()) {
                    $fileData = $this->params()->fromFiles('file');
                    if (0 === $fileData['error']) {
                        $this->messenger()->addSuccess('Vocabulary label and comment successfully updated. Please review these changes before you accept them.'); // @translate
                        $diff = $this->rdfImporter->getDiff(
                            'file',
                            $vocabulary->namespaceUri(),
                            ['file' => $fileData['tmp_name']]
                        );
                        $form = $this->getForm(VocabularyUpdateForm::class);
                        $form->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'update'], true));
                        $form->get('diff')->setValue(json_encode($diff));

                        $view->setVariable('diff', $diff);
                        $view->setTemplate('omeka/admin/vocabulary/update');
                    } else {
                        $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                    }
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
                $diff = json_decode($data['diff'], true);

                $em = $this->entityManager;
                $vocabulary = $em->find('Omeka\Entity\Vocabulary', $this->params('id'));
                $classRepo = $em->getRepository('Omeka\Entity\ResourceClass');
                $propertyRepo = $em->getRepository('Omeka\Entity\Property');

                foreach ($diff['properties']['new'] as $localName => $info) {
                    $property = new Property;
                    $property->setVocabulary($vocabulary);
                    $property->setLocalName($localName);
                    $property->setLabel($info[0]);
                    $property->setComment($info[1]);
                    $em->persist($property);
                }
                foreach ($diff['properties']['label'] as $localName => $change) {
                    $property = $propertyRepo->findOneBy([
                        'vocabulary' => $vocabulary,
                        'localName' => $localName,
                    ]);
                    $property->setLabel($change[1]);
                }
                foreach ($diff['properties']['comment'] as $localName => $change) {
                    $property = $propertyRepo->findOneBy([
                        'vocabulary' => $vocabulary,
                        'localName' => $localName,
                    ]);
                    $property->setComment($change[1]);
                }

                foreach ($diff['classes']['new'] as $localName => $info) {
                    $class = new ResourceClass;
                    $class->setVocabulary($vocabulary);
                    $class->setLocalName($localName);
                    $class->setLabel($info[0]);
                    $class->setComment($info[1]);
                    $em->persist($class);
                }
                foreach ($diff['classes']['label'] as $localName => $change) {
                    $class = $classRepo->findOneBy([
                        'vocabulary' => $vocabulary,
                        'localName' => $localName,
                    ]);
                    $class->setLabel($change[1]);
                }
                foreach ($diff['classes']['comment'] as $localName => $change) {
                    $class = $classRepo->findOneBy([
                        'vocabulary' => $vocabulary,
                        'localName' => $localName,
                    ]);
                    $class->setComment($change[1]);
                }

                $em->flush();
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
