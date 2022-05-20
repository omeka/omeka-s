<?php
namespace Omeka\Controller\Admin;

use Omeka\DataType\Manager as DataTypeManager;
use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceTemplateForm;
use Omeka\Form\ResourceTemplateImportForm;
use Omeka\Form\ResourceTemplateReviewImportForm;
use Omeka\Mvc\Exception\NotFoundException;
use Omeka\Stdlib\Message;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class ResourceTemplateController extends AbstractActionController
{
    protected $dataTypeManager;

    public function __construct(DataTypeManager $dataTypeManager)
    {
        $this->dataTypeManager = $dataTypeManager;
    }

    public function browseAction()
    {
        $this->browse()->setDefaults('resource_templates');
        $response = $this->api()->search('resource_templates', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

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
        $form = $this->params()->fromPost('import')
            ? $this->getForm(ResourceTemplateReviewImportForm::class)
            : $this->getForm(ResourceTemplateImportForm::class);
        $view = new ViewModel;

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $file = $this->params()->fromFiles('file');
                if ($file) {
                    // Process import form.
                    $import = json_decode(file_get_contents($file['tmp_name']), true);
                    if ($this->importIsValid($import)) {
                        $import = $this->flagValid($import);

                        $form = $this->getForm(ResourceTemplateReviewImportForm::class);
                        $form->get('import')->setValue(json_encode($import));
                        $form->get('label')->setValue($import['o:label']);

                        $view->setVariable('import', $import);
                        $view->setTemplate('omeka/admin/resource-template/review-import');
                    } else {
                        $this->messenger()->addError('Invalid import file format');
                    }
                } else {
                    // Process review import form.
                    $import = json_decode($form->getData()['import'], true);
                    $import['o:label'] = $this->params()->fromPost('label');

                    $dataTypes = $this->params()->fromPost('data_types');
                    if ($dataTypes) {
                        foreach ($dataTypes as $key => $dataTypeList) {
                            $import['o:resource_template_property'][$key]['o:data_type'] = $dataTypeList;
                        }
                    }

                    $response = $this->api($form)->create('resource_templates', $import);
                    if ($response) {
                        return $this->redirect()->toUrl($response->getContent()->url());
                    } else {
                        $form = $this->getForm(ResourceTemplateReviewImportForm::class);
                        $form->get('import')->setValue(json_encode($import));
                        $form->get('label')->setValue($import['o:label']);
                        $view->setVariable('import', $import);
                        $view->setTemplate('omeka/admin/resource-template/review-import');
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Flag members and data types as valid.
     *
     * All members start as invalid until we determine whether the corresponding
     * vocabulary and member exists in this installation. All data types start
     * as "Default" (i.e. none declared) until we determine whether they match
     * the native types (literal, uri, resource).
     *
     * We flag a valid vocabulary by adding [vocabulary_prefix] to the member; a
     * valid class by adding [o:id]; and a valid property by adding
     * [o:property][o:id]. We flag a valid data type by adding [o:data_type] to
     * the property. By design, the API will only hydrate members and data types
     * that are flagged as valid.
     *
     * @todo Manage direct import of data types from Value Suggest and other modules.
     *
     * @param array $import
     * @return array
     */
    protected function flagValid(array $import)
    {
        $vocabs = [];
        $dataTypes = [
            'literal',
            'uri',
            'resource',
            'resource:item',
            'resource:itemset',
            'resource:media',
        ];

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

        if (isset($import['o:resource_class'])) {
            if ($vocab = $getVocab($import['o:resource_class']['vocabulary_namespace_uri'])) {
                $import['o:resource_class']['vocabulary_prefix'] = $vocab->prefix();
                $class = $this->api()->searchOne('resource_classes', [
                    'vocabulary_namespace_uri' => $import['o:resource_class']['vocabulary_namespace_uri'],
                    'local_name' => $import['o:resource_class']['local_name'],
                ])->getContent();
                if ($class) {
                    $import['o:resource_class']['o:id'] = $class->id();
                }
            }
        }

        foreach (['o:title_property', 'o:description_property'] as $property) {
            if (isset($import[$property])) {
                if ($vocab = $getVocab($import[$property]['vocabulary_namespace_uri'])) {
                    $import[$property]['vocabulary_prefix'] = $vocab->prefix();
                    $prop = $this->api()->searchOne('properties', [
                        'vocabulary_namespace_uri' => $import[$property]['vocabulary_namespace_uri'],
                        'local_name' => $import[$property]['local_name'],
                    ])->getContent();
                    if ($prop) {
                        $import[$property]['o:id'] = $prop->id();
                    }
                }
            }
        }

        foreach ($import['o:resource_template_property'] as $key => $property) {
            if ($vocab = $getVocab($property['vocabulary_namespace_uri'])) {
                $import['o:resource_template_property'][$key]['vocabulary_prefix'] = $vocab->prefix();
                $prop = $this->api()->searchOne('properties', [
                    'vocabulary_namespace_uri' => $property['vocabulary_namespace_uri'],
                    'local_name' => $property['local_name'],
                ])->getContent();
                if ($prop) {
                    $import['o:resource_template_property'][$key]['o:property'] = ['o:id' => $prop->id()];
                    // Check the deprecated "data_type_name" if needed and
                    // normalize it.
                    if (!array_key_exists('data_types', $import['o:resource_template_property'][$key])) {
                        if (!empty($import['o:resource_template_property'][$key]['data_type_name'])
                            && !empty($import['o:resource_template_property'][$key]['data_type_label'])
                        ) {
                            $import['o:resource_template_property'][$key]['data_types'] = [[
                                'name' => $import['o:resource_template_property'][$key]['data_type_name'],
                                'label' => $import['o:resource_template_property'][$key]['data_type_label'],
                            ]];
                        } else {
                            $import['o:resource_template_property'][$key]['data_types'] = [];
                        }
                    }
                    $importDataTypes = [];
                    foreach ($import['o:resource_template_property'][$key]['data_types'] as $dataType) {
                        $importDataTypes[$dataType['name']] = $dataType;
                    }
                    $import['o:resource_template_property'][$key]['data_types'] = $importDataTypes;
                    // Prepare the list of standard data types.
                    $import['o:resource_template_property'][$key]['o:data_type'] = [];
                    foreach ($importDataTypes as $name => $importDataType) {
                        if (in_array($name, $dataTypes)) {
                            $import['o:resource_template_property'][$key]['o:data_type'][] = $importDataType['name'];
                        }
                    }
                }
            }
        }

        return $import;
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

        if (!isset($import['o:label']) || !is_string($import['o:label'])) {
            // missing or invalid label
            return false;
        }

        // Validate class.
        if (isset($import['o:resource_class'])) {
            if (!is_array($import['o:resource_class'])) {
                // invalid o:resource_class
                return false;
            }
            if (!array_key_exists('vocabulary_namespace_uri', $import['o:resource_class'])
                || !array_key_exists('vocabulary_label', $import['o:resource_class'])
                || !array_key_exists('local_name', $import['o:resource_class'])
                || !array_key_exists('label', $import['o:resource_class'])
            ) {
                // missing o:resource_class info
                return false;
            }
            if (!is_string($import['o:resource_class']['vocabulary_namespace_uri'])
                || !is_string($import['o:resource_class']['vocabulary_label'])
                || !is_string($import['o:resource_class']['local_name'])
                || !is_string($import['o:resource_class']['label'])
            ) {
                // invalid o:resource_class info
                return false;
            }
        }

        // Validate title and description.
        foreach (['o:title_property', 'o:description_property'] as $property) {
            if (isset($import[$property])) {
                if (!is_array($import[$property])) {
                    // Invalid property.
                    return false;
                }
                if (!array_key_exists('vocabulary_namespace_uri', $import[$property])
                    || !array_key_exists('vocabulary_label', $import[$property])
                    || !array_key_exists('local_name', $import[$property])
                    || !array_key_exists('label', $import[$property])
                ) {
                    // Missing a property info.
                    return false;
                }
                if (!is_string($import[$property]['vocabulary_namespace_uri'])
                    || !is_string($import[$property]['vocabulary_label'])
                    || !is_string($import[$property]['local_name'])
                    || !is_string($import[$property]['label'])
                ) {
                    // Invalid property info.
                    return false;
                }
            }
        }

        // Validate properties.
        if (!isset($import['o:resource_template_property']) || !is_array($import['o:resource_template_property'])) {
            // missing or invalid o:resource_template_property
            return false;
        }

        foreach ($import['o:resource_template_property'] as $property) {
            if (!is_array($property)) {
                // invalid o:resource_template_property format
                return false;
            }

            // Manage import from an export of Omeka < 3.0.
            $oldExport = !array_key_exists('data_types', $property);

            // Check missing o:resource_template_property info.
            if (!array_key_exists('vocabulary_namespace_uri', $property)
                || !array_key_exists('vocabulary_label', $property)
                || !array_key_exists('local_name', $property)
                || !array_key_exists('label', $property)
                || !array_key_exists('o:alternate_label', $property)
                || !array_key_exists('o:alternate_comment', $property)
                || !array_key_exists('o:is_required', $property)
                || !array_key_exists('o:is_private', $property)
            ) {
                return false;
            }
            if ($oldExport
                 && (!array_key_exists('data_type_name', $property)
                    || !array_key_exists('data_type_label', $property)
            )) {
                return false;
            }

            // Check invalid o:resource_template_property info.
            if (!is_string($property['vocabulary_namespace_uri'])
                || !is_string($property['vocabulary_label'])
                || !is_string($property['local_name'])
                || !is_string($property['label'])
                || (!is_string($property['o:alternate_label']) && !is_null($property['o:alternate_label']))
                || (!is_string($property['o:alternate_comment']) && !is_null($property['o:alternate_comment']))
                || !is_bool($property['o:is_required'])
                || !is_bool($property['o:is_private'])
            ) {
                return false;
            }
            if ($oldExport) {
                if ((!is_string($property['data_type_name']) && !is_null($property['data_type_name']))
                    || (!is_string($property['data_type_label']) && !is_null($property['data_type_label']))
                ) {
                    return false;
                }
            } elseif (!is_array($property['data_types']) && !is_null($property['data_types'])) {
                return false;
            }
        }
        return true;
    }

    public function exportAction()
    {
        /** @var \Omeka\Api\Representation\ResourceTemplateRepresentation $template */
        $template = $this->api()->read('resource_templates', $this->params('id'))->getContent();
        $templateClass = $template->resourceClass();
        $templateTitle = $template->titleProperty();
        $templateDescription = $template->descriptionProperty();
        $templateProperties = $template->resourceTemplateProperties();

        $export = [
            'o:label' => $template->label(),
            'o:resource_template_property' => [],
        ];

        if ($templateClass) {
            $vocab = $templateClass->vocabulary();
            $export['o:resource_class'] = [
                'vocabulary_namespace_uri' => $vocab->namespaceUri(),
                'vocabulary_label' => $vocab->label(),
                'local_name' => $templateClass->localName(),
                'label' => $templateClass->label(),
            ];
        }

        if ($templateTitle) {
            $vocab = $templateTitle->vocabulary();
            $export['o:title_property'] = [
                'vocabulary_namespace_uri' => $vocab->namespaceUri(),
                'vocabulary_label' => $vocab->label(),
                'local_name' => $templateTitle->localName(),
                'label' => $templateTitle->label(),
            ];
        }

        if ($templateDescription) {
            $vocab = $templateDescription->vocabulary();
            $export['o:description_property'] = [
                'vocabulary_namespace_uri' => $vocab->namespaceUri(),
                'vocabulary_label' => $vocab->label(),
                'local_name' => $templateDescription->localName(),
                'label' => $templateDescription->label(),
            ];
        }

        foreach ($templateProperties as $templateProperty) {
            $property = $templateProperty->property();
            $vocab = $property->vocabulary();

            // Note that "position" is implied by array order.
            $export['o:resource_template_property'][] = [
                'o:alternate_label' => $templateProperty->alternateLabel(),
                'o:alternate_comment' => $templateProperty->alternateComment(),
                'o:is_required' => $templateProperty->isRequired(),
                'o:is_private' => $templateProperty->isPrivate(),
                'data_types' => $templateProperty->dataTypeLabels(),
                'vocabulary_namespace_uri' => $vocab->namespaceUri(),
                'vocabulary_label' => $vocab->label(),
                'local_name' => $property->localName(),
                'label' => $property->label(),
            ];
        }

        $filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $template->label());
        $export = json_encode($export, JSON_PRETTY_PRINT);

        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'application/json')
                ->addHeaderLine('Content-Disposition', sprintf('attachment; filename="%s.json"', $filename))
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
        $view->setVariable('resourceLabel', 'resource template'); // @translate
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
                if ($response) {
                    $this->messenger()->addSuccess('Resource template successfully deleted'); // @translate
                } else {
                    $this->messenger()->addError('Resource template could not be deleted'); // @translate
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
            if ($data['o:title_property']) {
                $data['o:title_property[o:id]'] = $data['o:title_property']->id();
            }
            if ($data['o:description_property']) {
                $data['o:description_property[o:id]'] = $data['o:description_property']->id();
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
                if ($response) {
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

        if ($this->getRequest()->isPost()) {
            // Set POSTed property rows
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
        } else {
            // Set default property rows
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
                        'o:data_type' => $resTemProp->dataTypes(),
                        'o:is_required' => $resTemProp->isRequired(),
                        'o:is_private' => $resTemProp->isPrivate(),
                    ];
                }
            } else {
                // For the add action, dcterms:title and dcterms:description are
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
                        'o:is_private' => false,
                    ],
                    [
                        'o:property' => $descriptionProperty,
                        'o:alternate_label' => null,
                        'o:alternate_comment' => null,
                        'o:data_type' => null,
                        'o:is_required' => false,
                        'o:is_private' => false,
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
            'o:is_private' => false,
        ];

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/resource-template/show-property-row');
        $view->setVariable('propertyRow', $propertyRow);
        return $view;
    }
}
