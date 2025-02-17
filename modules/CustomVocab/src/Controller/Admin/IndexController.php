<?php
namespace CustomVocab\Controller\Admin;

use CustomVocab\Form\CustomVocabForm;
use CustomVocab\Form\CustomVocabImportForm;
use CustomVocab\Stdlib\ImportExport;
use Omeka\Form\ConfirmForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $importExport;

    public function __construct(ImportExport $importExport)
    {
        $this->importExport = $importExport;
    }

    public function browseAction()
    {
        $this->browse()->setDefaults('custom_vocabs');
        $response = $this->api()->search('custom_vocabs', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());
        $view = new ViewModel;
        $view->setVariable('vocabs', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('custom_vocabs', $this->params('id'));
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(CustomVocabForm::class);

        if ($this->getRequest()->isPost()) {
            $importFile = $this->params()->fromFiles('import_file');
            if ($importFile) {
                // Handle an import from file.
                $importForm = $this->getForm(CustomVocabImportForm::class);
                $importForm->setData($this->params()->fromPost());
                if ($importForm->isValid()) {
                    $import = json_decode(file_get_contents($importFile['tmp_name']), true);
                    if ($this->importExport->isValidImport($import)) {
                        $this->messenger()->addSuccess('Custom vocab file applied to this form. Check for accuracy and submit to save.'); // @translate
                        $form->setData($import);
                    } else {
                        $this->messenger()->addError('Invalid custom vocab file.'); // @translate
                        return $this->redirect()->toRoute('admin/custom-vocab/default', ['action' => 'import']);
                    }
                } else {
                    $this->messenger()->addFormErrors($form);
                    return $this->redirect()->toRoute('admin/custom-vocab/default', ['action' => 'import']);
                }
            } else {
                // Handle a submission.
                $form->setData($this->params()->fromPost());
                if ($form->isValid()) {
                    $formData = $this->processFormData($form->getData());
                    $response = $this->api($form)->create('custom_vocabs', $formData);
                    if ($response) {
                        $this->messenger()->addSuccess('Custom vocab created.'); // @translate
                        return $this->redirect()->toRoute('admin/custom-vocab');
                    }
                } else {
                    $this->messenger()->addError('Cannot add custom vocab.'); // @translate
                }
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $form = $this->getForm(CustomVocabForm::class);
        $response = $this->api()->read('custom_vocabs', $this->params('id'));
        $vocab = $response->getContent();

        if ($this->getRequest()->isPost()) {
            $importFile = $this->params()->fromFiles('import_file');
            if ($importFile) {
                // Handle an update from file.
                $importForm = $this->getForm(CustomVocabImportForm::class);
                $importForm->setData($this->params()->fromPost());
                if ($importForm->isValid()) {
                    $import = json_decode(file_get_contents($importFile['tmp_name']), true);
                    if ($this->importExport->isValidImport($import)) {
                        $this->messenger()->addSuccess('Custom vocab file applied to this form. Check for accuracy and submit to save.'); // @translate
                        $form->setData($import);
                    } else {
                        $this->messenger()->addError('Invalid custom vocab file.'); // @translate
                        return $this->redirect()->toRoute('admin/custom-vocab/id', ['action' => 'import', 'id' => $this->params('id')]);
                    }
                } else {
                    $this->messenger()->addFormErrors($form);
                    return $this->redirect()->toRoute('admin/custom-vocab/id', ['action' => 'import', 'id' => $this->params('id')]);
                }
            } else {
                $form->setData($this->params()->fromPost());
                if ($form->isValid()) {
                    $formData = $this->processFormData($form->getData());
                    $response = $this->api($form)->update('custom_vocabs', $vocab->id(), $formData);
                    if ($response) {
                        $this->messenger()->addSuccess('Custom vocab updated.'); // @translate
                        return $this->redirect()->toRoute('admin/custom-vocab');
                    }
                } else {
                    $this->messenger()->addError('Cannot edit custom vocab.'); // @translate
                }
            }
        } else {
            $data = $vocab->jsonSerialize();
            $data['o:item_set'] = $data['o:item_set'] ? $data['o:item_set']->id() : null;
            $form->setData($data);
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('vocab', $vocab);
        return $view;
    }

    public function exportAction()
    {
        $export = $this->importExport->getExport($this->params('id'));
        if (!$export) {
            $this->messenger()->addError('Cannot export custom vocab.'); // @translate
            return $this->redirect()->toRoute('admin/custom-vocab');
        }
        $filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $export['o:label']);
        $exportJson = json_encode($export, JSON_PRETTY_PRINT);

        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/json')
            ->addHeaderLine('Content-Disposition', sprintf('attachment; filename="%s.json"', $filename))
            ->addHeaderLine('Content-Length', strlen($exportJson));
        $response->setContent($exportJson);
        return $response;
    }

    public function importAction()
    {
        $form = $this->getForm(CustomVocabImportForm::class);
        if ($this->params('id')) {
            $vocab = $this->api()->read('custom_vocabs', $this->params('id'))->getContent();
            $form->setAttribute('action', $this->url()->fromRoute('admin/custom-vocab/id', ['action' => 'edit', 'id' => $this->params('id')]));
        } else {
            $vocab = null;
            $form->setAttribute('action', $this->url()->fromRoute('admin/custom-vocab/default', ['action' => 'add']));
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('vocab', $vocab);
        return $view;
    }

    /**
     * Prepare form data for create/update operation.
     *
     * Given a vocab type, this sets the other vocab type's data to null. This
     * will ensure that the API saves only the relevant data.
     *
     * @param $formData
     * @return array
     */
    protected function processFormData($formData)
    {
        $formData['o:item_set'] = ['o:id' => $formData['o:item_set']];
        switch ($formData['vocab_type']) {
            case 'resource':
                $formData['o:terms'] = null;
                $formData['o:uris'] = null;
                break;
            case 'uri':
                $formData['o:item_set'] = null;
                $formData['o:terms'] = null;
                break;
            case 'literal':
            default:
                $formData['o:item_set'] = null;
                $formData['o:uris'] = null;
        }
        return $formData;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('custom_vocabs', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Vocab successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addError('Vocab could not be deleted'); // @translate
            }
        }
        return $this->redirect()->toRoute('admin/custom-vocab');
    }

    public function deleteConfirmAction()
    {
        $resource = $this->api()->read('custom_vocabs', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $resource);
        $resourceLabel = 'custom vocab'; // @translate
        $view->setVariable('resourceLabel', $resourceLabel);
        $view->setVariable('partialPath', 'custom-vocab/admin/index/show-details');
        return $view;
    }
}
