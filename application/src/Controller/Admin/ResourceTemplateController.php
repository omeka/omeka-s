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
                            list($template, $vocabs) = $this->getImportCompatibility($import);

                            $form = $this->getForm(ResourceTemplateReviewImportForm::class);
                            $form->get('resource_template')->setValue(json_encode($template));

                            $view->setVariable('template', $template);
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
     * Derive what is compatible and incompatible from the import.
     *
     * @param array $import
     * @return array
     */
    protected function getImportCompatibility(array $import)
    {
        $template = $import['resource_template'];
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

        if ($vocab = $getVocab($template['o:resource_class']['namespace_uri'])) {
            $class = $this->api()->searchOne('resource_classes', [
                'vocabulary_namespace_uri' => $template['o:resource_class']['namespace_uri'],
                'local_name' => $template['o:resource_class']['local_name'],
            ])->getContent();
            if ($class) {
                $template['o:resource_class']['o:id'] = $class->id();
            }
        }

        foreach ($template['o:resource_template_property'] as $key => $property) {
            if ($vocab = $getVocab($property['namespace_uri'])) {
                $prop = $this->api()->searchOne('properties', [
                    'vocabulary_namespace_uri' => $property['namespace_uri'],
                    'local_name' => $property['local_name'],
                ])->getContent();
                if ($prop) {
                    $template['o:resource_template_property'][$key]['o:property'] = ['o:id' => $prop->id()];
                }
            }
        }

        return [$template, $vocabs];
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
        if (!array_key_exists('vocabularies', $import) || !is_array($import['vocabularies'])) {
            return false;
        }
        if (!array_key_exists('resource_template', $import) || !is_array($import['resource_template'])) {
            return false;
        }

        $vocabs = $import['vocabularies'];
        $template = $import['resource_template'];

        if (!isset($template['o:label'])) {
            // missing label
            return false;
        }
        if (!is_string($template['o:label'])) {
            // invalid label
            return false;
        }

        // Validate class.
        if (!array_key_exists('o:resource_class', $template)) { // class can be null
            // missing class
            return false;
        }
        if (!is_array($template['o:resource_class']) && null !== $template['o:resource_class']) {
            // invalid class format
            return false;
        }
        if (is_array($template['o:resource_class'])) {
            if (!array_key_exists('namespace_uri', $template['o:resource_class'])
                || !array_key_exists('local_name', $template['o:resource_class'])
                || !array_key_exists('label', $template['o:resource_class'])
                || !array_key_exists('comment', $template['o:resource_class'])
            ) {
                // missing class info
                return false;
            }
            if (!is_string($template['o:resource_class']['namespace_uri'])
                || !is_string($template['o:resource_class']['local_name'])
                || !is_string($template['o:resource_class']['label'])
                || (!is_string($template['o:resource_class']['comment']) && null !== $template['o:resource_class']['comment'])
            ) {
                // invalid class info
                return false;
            }
        }

        // Validate properties.
        if (!isset($template['o:resource_template_property'])) {
            // missing properties
            return false;
        }
        if (!is_array($template['o:resource_template_property'])) {
            // invalid properties format
            return false;
        }

        foreach ($template['o:resource_template_property'] as $property) {
            if (!is_array($property)) {
                // invalid property format
                return false;
            }
            if (!array_key_exists('namespace_uri', $property)
                || !array_key_exists('local_name', $property)
                || !array_key_exists('label', $property)
                || !array_key_exists('comment', $property)
                || !array_key_exists('o:alternate_label', $property)
                || !array_key_exists('o:alternate_comment', $property)
                || !array_key_exists('o:is_required', $property)
                || !array_key_exists('o:data_type', $property)
            ) {
                // missing property info
                return false;
            }
            if (!is_string($property['namespace_uri'])
                || !is_string($property['local_name'])
                || !is_string($property['label'])
                || (!is_string($property['comment']) && !is_null($property['comment']))
                || (!is_string($property['o:alternate_label']) && !is_null($property['o:alternate_label']))
                || (!is_string($property['o:alternate_comment']) && !is_null($property['o:alternate_comment']))
                || !is_bool($property['o:is_required'])
                || (!is_string($property['o:data_type']) && !is_null($property['o:data_type']))
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
            'vocabularies' => [],
            'resource_template' => [
                'o:label' => $template->label(),
                'o:resource_class' => null,
                'o:resource_template_property' => [],
            ],
        ];

        if ($templateClass) {
            $vocab = $templateClass->vocabulary();
            $export['vocabularies'][$vocab->namespaceUri()] = [
                'label' => $vocab->label(),
                'comment' => $vocab->comment(),
            ];
            $export['resource_template']['o:resource_class'] = [
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
            $export['resource_template']['o:resource_template_property'][] = [
                'o:alternate_label' => $templateProperty->alternateLabel(),
                'o:alternate_comment' => $templateProperty->alternateComment(),
                'o:is_required' => $templateProperty->isRequired(),
                'o:data_type' => $templateProperty->dataType(),
                'namespace_uri' => $vocab->namespaceUri(),
                'local_name' => $property->localName(),
                'label' => $property->label(),
                'comment' => $property->comment(),
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
