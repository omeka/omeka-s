<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceTemplateForm;
use Omeka\Form\ResourceTemplateImportForm;
use Omeka\Form\ResourceTemplateReviewImportForm;
use Omeka\Mvc\Exception\NotFoundException;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ResourceTemplateController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('label');
        $response = $this->api()->search('resource_templates', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('resourceTemplates', $response->getContent());
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('resource_templates', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('resourceTemplate', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('resource_templates', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function importAction()
    {
        $form = $this->getForm(ResourceTemplateImportForm::class);
        $view = new ViewModel;

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $file = $this->params()->fromFiles('file');
                if ($file) {
                    // Process import form.
                    $import = json_decode(file_get_contents($file['tmp_name']), true);
                    if (JSON_ERROR_NONE === json_last_error()) {
                        if ($this->importIsValid($import)) {
                            list($template, $notFound, $vocabs) = $this->getImportCompatibility($import);

                            $form = $this->getForm(ResourceTemplateReviewImportForm::class);
                            $form->get('import')->setValue(json_encode($import));

                            $view->setVariable('template', $template);
                            $view->setVariable('notFound', $notFound);
                            $view->setVariable('vocabs', $vocabs);
                            $view->setTemplate('omeka/admin/resource-template/review-import');
                        } else {
                            $this->messenger()->addError('Invalid import file format');
                        }
                    } else {
                        $this->messenger()->addError('Invalid import file');
                    }
                } else {
                    // Process review import form.
                    $import = $this->params()->fromPost('import');
                    echo '<pre>';print_r($import);echo '</pre>';exit;
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Derive what is compatible and incompatible in the import.
     *
     * @param array $import
     * @return array
     */
    protected function getImportCompatibility(array $import)
    {
        $template = [
            'o:label' => $import['label'],
            'o:resource_class' => null,
            'o:resource_template_property' => [],
        ];
        $notFound = [
            'vocabularies' => [],
            'class' => null,
            'properties' => [],
        ];
        $vocabs = [];

        $getVocab = function ($namespaceUri) use (&$vocabs) {
            if (isset($vocabs[$namespaceUri])) {
                return $vocabs[$namespaceUri];
            }
            $vocab = $this->api()->searchOne('vocabularies', [
                'namespace_uri' => $namespaceUri,
            ])->getContent();
            if ($vocab) {
                $vocabs[$namespaceUri] = $vocab;
                return $vocab;
            }
            return false;
        };

        if ($vocab = $getVocab($import['class']['namespace_uri'])) {
            $class = $this->api()->searchOne('resource_classes', [
                'vocabulary_namespace_uri' => $import['class']['namespace_uri'],
                'local_name' => $import['class']['local_name'],
            ])->getContent();
            if ($class) {
                $template['o:resource_class'] = [
                    'o:id' => $class->id(),
                    'namespace_uri' => $import['class']['namespace_uri'],
                    'local_name' => $class->localName(),
                    'label' => $class->label(),
                    'comment' => $class->comment(),
                ];
            } else {
                $notFound['class'] = $import['class'];
            }
        } else {
            $notFound['class'] = $import['class'];
            $notFound['vocabularies'][] = $import['class']['namespace_uri'];
        }

        foreach ($import['properties'] as $property) {
            if ($vocab = $getVocab($property['namespace_uri'])) {
                $prop = $this->api()->searchOne('properties', [
                    'vocabulary_namespace_uri' => $property['namespace_uri'],
                    'local_name' => $property['local_name'],
                ])->getContent();
                if ($prop) {
                    $template['o:resource_template_property'][] = [
                        'o:property' => ['o:id' => $prop->id()],
                        'o:alternate_label' => $property['alternate_label'],
                        'o:alternate_comment' => $property['alternate_comment'],
                        'o:is_required' => $property['is_required'],
                        'namespace_uri' => $property['namespace_uri'],
                        'local_name' => $prop->localName(),
                        'label' => $prop->label(),
                        'comment' => $prop->comment(),
                    ];
                } else {
                    $notFound['properties'][] = $property;
                }
            } else {
                $notFound['properties'][] = $property;
                $notFound['vocabularies'][] = $property['namespace_uri'];
            }
        }

        return [$template, $notFound, $vocabs];
    }

    /**
     * Verify that the import format is valid.
     *
     * @param array $import
     * @return bool
     */
    protected function importIsValid($import)
    {
        if (!is_array($import)) {
            // invalid format
            return false;
        }

        if (!isset($import['label'])) {
            // missing label
            return false;
        }
        if (!is_string($import['label'])) {
            // invalid label
            return false;
        }

        // Validate class.
        if (!array_key_exists('class', $import)) { // class can be null
            // missing class
            return false;
        }
        if (!is_array($import['class']) && null !== $import['class']) {
            // invalid class format
            return false;
        }
        if (is_array($import['class'])) {
            if (!array_key_exists('namespace_uri', $import['class'])
                || !array_key_exists('local_name', $import['class'])
                || !array_key_exists('label', $import['class'])
                || !array_key_exists('comment', $import['class'])
            ) {
                // missing class info
                return false;
            }
            if (!is_string($import['class']['namespace_uri'])
                || !is_string($import['class']['local_name'])
                || !is_string($import['class']['label'])
                || (!is_string($import['class']['comment']) && null !== $import['class']['comment'])
            ) {
                // invalid class info
                return false;
            }
        }

        // Validate properties.
        if (!isset($import['properties'])) {
            // missing properties
            return false;
        }
        if (!is_array($import['properties'])) {
            // invalid properties format
            return false;
        }

        foreach ($import['properties'] as $property) {
            if (!is_array($property)) {
                // invalid property format
                return false;
            }
            if (!array_key_exists('namespace_uri', $property)
                || !array_key_exists('local_name', $property)
                || !array_key_exists('label', $property)
                || !array_key_exists('comment', $property)
                || !array_key_exists('alternate_label', $property)
                || !array_key_exists('alternate_comment', $property)
                || !array_key_exists('is_required', $property)
                || !array_key_exists('data_type', $property)
            ) {
                // missing property info
                return false;
            }
            if (!is_string($property['namespace_uri'])
                || !is_string($property['local_name'])
                || !is_string($property['label'])
                || (!is_string($property['comment']) && !is_null($property['comment']))
                || (!is_string($property['alternate_label']) && !is_null($property['alternate_label']))
                || (!is_string($property['alternate_comment']) && !is_null($property['alternate_comment']))
                || !is_bool($property['is_required'])
                || (!is_string($property['data_type']) && !is_null($property['data_type']))
            ) {
                // invalid property info
                return false;
            }
        }
        return true;
    }

    public function exportAction()
    {
        $template = $this->api()->read('resource_templates', $this->params('id'))->getContent();
        $templateClass = $template->resourceClass();
        $templateProperties = $template->resourceTemplateProperties();

        $export = [
            'label' => $template->label(),
            'vocabularies' => [],
            'class' => null,
            'properties' => [],
        ];

        if ($templateClass) {
            $vocab = $templateClass->vocabulary();
            $export['vocabularies'][$vocab->namespaceUri()] = [
                'label' => $vocab->label(),
                'comment' => $vocab->comment(),
            ];
            $export['class'] = [
                'namespace_uri' => $vocab->namespaceUri(),
                'local_name' => $templateClass->localName(),
                'label' => $templateClass->label(),
                'comment' => $templateClass->comment(),
            ];
        }

        foreach ($templateProperties as $templateProperty) {
            $property = $templateProperty->property();
            $vocab = $property->vocabulary();

            $export['vocabularies'][$vocab->namespaceUri()] = [
                'label' => $vocab->label(),
                'comment' => $vocab->comment(),
            ];
            // Note that "position" is implied by array order.
            $export['properties'][] = [
                'namespace_uri' => $vocab->namespaceUri(),
                'local_name' => $property->localName(),
                'label' => $property->label(),
                'comment' => $property->comment(),
                'alternate_label' => $templateProperty->alternateLabel(),
                'alternate_comment' => $templateProperty->alternateComment(),
                'is_required' => $templateProperty->isRequired(),
                'data_type' => $templateProperty->dataType(),
            ];
        }

        $export = json_encode($export, JSON_PRETTY_PRINT);

        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/json')
                ->addHeaderLine('Content-Disposition', 'attachment; filename="resource_template_export.json"')
                ->addHeaderLine('Content-Length', strlen($export));
        $response->setHeaders($headers);
        $response->setContent($export);
        return $response;
    }

    public function deleteConfirmAction()
    {
        $resource = $this->api()->read('resource_templates', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $resource);
        $view->setVariable('resourceLabel', 'resource template');
        $view->setVariable('partialPath', 'omeka/admin/resource-template/show-details');
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('resource_templates', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Resource template could not be deleted'); // @translate
                } else {
                    $this->messenger()->addSuccess('Resource template successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addError('Resource template could not be deleted'); // @translate
            }
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function addAction()
    {
        return $this->getAddEditView();
    }

    public function editAction()
    {
        return $this->getAddEditView();
    }

    /**
     * Get the add/edit view.
     *
     * @return ViewModel
     */
    protected function getAddEditView()
    {
        $action = $this->params('action');
        $form = $this->getForm(ResourceTemplateForm::class);

        if ('edit' == $action) {
            $resourceTemplate = $this->api()
                ->read('resource_templates', $this->params('id'))
                ->getContent();
            $data = $resourceTemplate->jsonSerialize();
            if ($data['o:resource_class']) {
                $data['o:resource_class[o:id]'] = $data['o:resource_class']->id();
            }
            $form->setData($data);
        }

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $response = ('edit' === $action)
                    ? $this->api($form)->update('resource_templates', $resourceTemplate->id(), $data)
                    : $this->api($form)->create('resource_templates', $data);
                if ($response->isSuccess()) {
                    if ('edit' === $action) {
                        $successMessage = 'Resource template successfully updated'; // @translate
                    } else {
                        $successMessage = new Message(
                            'Resource template successfully created. %s', // @translate
                            sprintf(
                                '<a href="%s">%s</a>',
                                htmlspecialchars($this->url()->fromRoute(null, [], true)),
                                $this->translate('Add another resource template?')
                            )
                        );
                        $successMessage->setEscapeHtml(false);
                    }
                    $this->messenger()->addSuccess($successMessage);
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        if ('edit' === $action) {
            $view->setVariable('resourceTemplate', $resourceTemplate);
        }
        $view->setVariable('propertyRows', $this->getPropertyRows());
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Get the property rows for the add/edit form.
     *
     * @return array
     */
    protected function getPropertyRows()
    {
        $action = $this->params('action');

        // Set POSTed property rows
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $propertyRows = $data['o:resource_template_property'];
            foreach ($propertyRows as $key => $propertyRow) {
                if (!isset($propertyRow['o:property']['o:id'])) {
                    // No property ID indicates that the property was removed.
                    unset($propertyRows[$key]);
                    continue;
                }
                $property = $this->api()->read(
                    'properties', $propertyRow['o:property']['o:id']
                )->getContent();
                $propertyRows[$property->id()]['o:property'] = $property;
            }

        // Set default property rows.
        } else {
            $propertyRows = [];
            if ('edit' == $action) {
                $resourceTemplate = $this->api()
                    ->read('resource_templates', $this->params('id'))
                    ->getContent();
                $resTemProps = $resourceTemplate->resourceTemplateProperties();
                foreach ($resTemProps as $key => $resTemProp) {
                    $propertyRows[$key] = [
                        'o:property' => $resTemProp->property(),
                        'o:alternate_label' => $resTemProp->alternateLabel(),
                        'o:alternate_comment' => $resTemProp->alternateComment(),
                        'o:data_type' => $resTemProp->dataType(),
                        'o:is_required' => $resTemProp->isRequired(),
                    ];
                }
            } else {
                // For the add action, determs:title and dcterms:description are
                // the only default property rows.
                $titleProperty = $this->api()->searchOne(
                    'properties', ['term' => 'dcterms:title']
                )->getContent();
                $descriptionProperty = $this->api()->searchOne(
                    'properties', ['term' => 'dcterms:description']
                )->getContent();
                $propertyRows = [
                    [
                        'o:property' => $titleProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                        'o:data_type' => null,
                        'o:is_required' => false,
                    ],
                    [
                        'o:property' => $descriptionProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                        'o:data_type' => null,
                        'o:is_required' => false,
                    ],
                ];
            }
        }

        return $propertyRows;
    }

    /**
     * Return a new property row for the add-edit page.
     */
    public function addNewPropertyRowAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new NotFoundException;
        }

        $property = $this->api()
            ->read('properties', $this->params()->fromQuery('property_id'))
            ->getContent();
        $propertyRow = [
            'o:property' => $property,
            'o:alternate_label' => null,
            'o:alternate_comment' => null,
            'o:data_type' => null,
            'o:is_required' => false,
        ];

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/resource-template/show-property-row');
        $view->setVariable('propertyRow', $propertyRow);
        return $view;
    }
}
