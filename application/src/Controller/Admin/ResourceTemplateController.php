<?php
namespace Omeka\Controller\Admin;

use Omeka\DataType\Manager as DataTypeManager;
use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceTemplateForm;
use Omeka\Form\ResourceTemplateImportForm;
use Omeka\Form\ResourceTemplatePropertyFieldset;
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
        $this->setBrowseDefaults('label', 'asc');
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
                    $dataTypes = $this->params()->fromPost('data_types', []);

                    $import['o:label'] = $this->params()->fromPost('label');
                    foreach ($dataTypes as $key => $dataType) {
                        $import['o:resource_template_property'][$key]['o:data_type'] = $dataType;
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

        foreach ($import['o:resource_template_property'] as $key => $property) {
            if ($vocab = $getVocab($property['vocabulary_namespace_uri'])) {
                $import['o:resource_template_property'][$key]['vocabulary_prefix'] = $vocab->prefix();
                $prop = $this->api()->searchOne('properties', [
                    'vocabulary_namespace_uri' => $property['vocabulary_namespace_uri'],
                    'local_name' => $property['local_name'],
                ])->getContent();
                if ($prop) {
                    $import['o:resource_template_property'][$key]['o:property'] = ['o:id' => $prop->id()];
                    if (in_array($import['o:resource_template_property'][$key]['data_type_name'], $dataTypes)) {
                        $import['o:resource_template_property'][$key]['o:data_type'] = $import['o:resource_template_property'][$key]['data_type_name'];
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
            if (!array_key_exists('vocabulary_namespace_uri', $property)
                || !array_key_exists('vocabulary_label', $property)
                || !array_key_exists('local_name', $property)
                || !array_key_exists('label', $property)
                || !array_key_exists('o:alternate_label', $property)
                || !array_key_exists('o:alternate_comment', $property)
                || !array_key_exists('o:is_required', $property)
                || !array_key_exists('o:is_private', $property)
                || !array_key_exists('data_type_name', $property)
                || !array_key_exists('data_type_label', $property)
            ) {
                // missing o:resource_template_property info
                return false;
            }
            if (!is_string($property['vocabulary_namespace_uri'])
                || !is_string($property['vocabulary_label'])
                || !is_string($property['local_name'])
                || !is_string($property['label'])
                || (!is_string($property['o:alternate_label']) && !is_null($property['o:alternate_label']))
                || (!is_string($property['o:alternate_comment']) && !is_null($property['o:alternate_comment']))
                || !is_bool($property['o:is_required'])
                || !is_bool($property['o:is_private'])
                || (!is_string($property['data_type_name']) && !is_null($property['data_type_name']))
                || (!is_string($property['data_type_label']) && !is_null($property['data_type_label']))
            ) {
                // invalid o:resource_template_property info
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

        foreach ($templateProperties as $templateProperty) {
            $property = $templateProperty->property();
            $vocab = $property->vocabulary();

            $dataTypeName = $templateProperty->dataType();
            $dataTypeLabel = null;
            if ($dataTypeName) {
                $dataType = $this->dataTypeManager->get($dataTypeName);
                $dataTypeLabel = $dataType->getLabel();
            }

            // Note that "position" is implied by array order.
            $export['o:resource_template_property'][] = [
                'o:alternate_label' => $templateProperty->alternateLabel(),
                'o:alternate_comment' => $templateProperty->alternateComment(),
                'o:is_required' => $templateProperty->isRequired(),
                'o:is_private' => $templateProperty->isPrivate(),
                'data_type_name' => $dataTypeName,
                'data_type_label' => $dataTypeLabel,
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
        return $this->getAddEditView(false);
    }

    public function editAction()
    {
        return $this->getAddEditView(true);
    }

    /**
     * Get the add/edit view.
     *
     * @var bool $isUpdate
     * @return ViewModel
     */
    protected function getAddEditView($isUpdate = false)
    {
        /**
         * @var \Omeka\Form\ResourceTemplateForm$form
         * @var \Omeka\Form\ResourceTemplatePropertyFieldset $rowSettingsFieldset
         */
        $form = $this->getForm(ResourceTemplateForm::class);
        $propertyFieldset = $this->getForm(ResourceTemplatePropertyFieldset::class);

        $isPost = $this->getRequest()->isPost();
        if ($isUpdate) {
            $resourceTemplate = $this->api()
                ->read('resource_templates', $this->params('id'))
                ->getContent();
            if (!$isPost) {
                // Recursive conversion into a json array.
                $data = json_decode(json_encode($resourceTemplate), true);
                $data = $this->fixDataArray($data);
                $form->setData($data);
            }
        } elseif (!$isPost) {
            $data = $this->getDefaultResourceTemplate();
            $data = $this->fixDataArray($data);
            $form->setData($data);
        }

        if ($isPost) {
            $post = $this->params()->fromPost();
            // For an undetermined reason, the fieldset "o:settings" inside the
            // collection is not validated. So elements should be attached to
            // the property fieldset with attribute "data-setting-key", so then
            // can be moved in "o:settings" after automatic filter and validation.
            $post = $this->fixPostArray($post);
            $post = $this->fixDataArray($post);
            $form->setData($post);
            if ($form->isValid()) {
                $data = $this->fixDataPostArray($form->getData());
                $response = $isUpdate
                    ? $this->api($form)->update('resource_templates', $resourceTemplate->id(), $data)
                    : $this->api($form)->create('resource_templates', $data);
                if ($response) {
                    if ($isUpdate) {
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
                $this->messenger()->addFormErrors($form);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        return new ViewModel([
            'resourceTemplate' => $isUpdate ? $resourceTemplate : null,
            'form' => $form,
            'propertyFieldset' => $propertyFieldset,
        ]);
    }

    protected function fixDataArray(array $data)
    {
        if (!empty($data['o:resource_class'])) {
            $data['o:resource_class[o:id]'] = $data['o:resource_class']['o:id'];
        }
        if (!empty($data['o:title_property'])) {
            $data['o:title_property[o:id]'] = $data['o:title_property']['o:id'];
        }
        if (!empty($data['o:description_property'])) {
            $data['o:description_property[o:id]'] = $data['o:description_property']['o:id'];
        }
        foreach ($data['o:resource_template_property'] as $key => $value) {
            $data['o:resource_template_property'][$key]['o:property[o:id]'] = $value['o:property']['o:id'];
            unset($data['o:resource_template_property'][$key]['o:property[o:id']);
            if (!empty($value['o:settings'])) {
                $data['o:resource_template_property'][$key] = array_merge($data['o:resource_template_property'][$key], $value['o:settings']);
            }
        }
        return $data;
    }

    protected function fixPostArray(array $post)
    {
        $post['o:resource_template_property'] = array_values($post['o:resource_template_property']);
        foreach ($post['o:resource_template_property'] as $key => $value) {
            if (empty($value['o:property[o:id'])) {
                unset($post['o:resource_template_property'][$key]);
                continue;
            }
            $post['o:resource_template_property'][$key]['o:property']['o:id'] = $value['o:property[o:id'];
            // Kept to manage issues.
            $post['o:resource_template_property'][$key]['o:property[o:id]'] = $value['o:property[o:id'];
            unset($post['o:resource_template_property'][$key]['o:property[o:id']);
            if (!empty($value['o:settings'])) {
                $post['o:resource_template_property'][$key] = array_merge($post['o:resource_template_property'][$key], $value['o:settings']);
            }
        }
        return $post;
    }

    protected function fixDataPostArray(array $data)
    {
        $propertyFieldset = $this->getForm(ResourceTemplatePropertyFieldset::class);
        $settingKeys = [];
        foreach ($propertyFieldset->getElements() as $element) {
            $settingKey = $element->getAttribute('data-setting-key');
            if ($settingKey) {
                $settingKeys[$element->getName()] = $settingKey;
            }
        }
        // Move settings into o:settings and remove empty settings.
        $data += [
            'o:resource_class' => empty($data['o:resource_class[o:id]']) ? null : ['o:id' => (int) $data['o:resource_class[o:id]']],
            'o:title_property' => empty($data['o:title_property[o:id]']) ? null : ['o:id' => (int) $data['o:title_property[o:id]']],
            'o:description_property' => empty($data['o:description_property[o:id]']) ? null : ['o:id' => (int) $data['o:description_property[o:id]']],
        ];
        unset($data['o:resource_class[o:id]'], $data['o:title_property[o:id]'], $data['o:description_property[o:id]'], $data['csrf']);
        foreach ($data['o:resource_template_property'] as $key => $value) {
            $data['o:resource_template_property'][$key]['o:property']['o:id'] = $data['o:resource_template_property'][$key]['o:property[o:id]'];
            unset($data['o:resource_template_property'][$key]['o:property[o:id]']);
            $data['o:resource_template_property'][$key]['o:settings'] = array_filter(array_intersect_key($value, $settingKeys), function ($v) {
                return is_string($v) ? strlen(trim($v)) : !empty($v);
            });
            $data['o:resource_template_property'][$key] = array_diff_key($data['o:resource_template_property'][$key], $settingKeys);
        }
        return $data;
    }

    /**
     * Get the default resource template.
     *
     * @return array
     */
    protected function getDefaultResourceTemplate()
    {
        $resourceTemplate = [
            'o:label' => '',
            'o:owner' => ['o:id' => $this->identity()->getId()],
            'o:resource_class' => null,
            'o:title_property' => null,
            'o:description_property' => null,
            'o:settings' => [],
            'o:resource_template_property' => [],
        ];

        $defaultProperties = ['dcterms:title', 'dcterms:description'];
        foreach ($defaultProperties as $property) {
            $property = $this->api()->searchOne(
                'properties', ['term' => $property]
            )->getContent();
            // In a Collection, "false" is not allowed for a checkbox, etc, except with input filter.
            $resourceTemplate['o:resource_template_property'][] = [
                'o:property' => ['o:id' => $property->id()],
                'o:alternate_label' => '',
                'o:alternate_comment' => '',
                'o:data_type' => '',
                'o:is_required' => 0,
                'o:is_private' => 0,
                'o:settings' => [],
            ];
        }

        return $resourceTemplate;
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

        $propertyFieldset = $this->getForm(\Omeka\Form\ResourceTemplatePropertyFieldset::class);
        $propertyFieldset->get('o:property[o:id]')->setValue($property->id());

        $namePrefix = 'o:resource_template_property[' . rand(PHP_INT_MAX / 1000000, PHP_INT_MAX) . ']';
        $propertyFieldset->setName($namePrefix);
        foreach ($propertyFieldset->getElements()  as $element) {
            $element->setName($namePrefix . '[' . $element->getName() . ']');
        }
        foreach ($propertyFieldset->getFieldsets()  as $fieldset) {
            $fieldset->setName($namePrefix . '[' . $fieldset->getName() . ']');
        }

        $view = new ViewModel([
            'property' => $property,
            'resourceTemplate' => null,
            'propertyFieldset' => $propertyFieldset,
        ]);
        return $view
            ->setTerminal(true)
            ->setTemplate('omeka/admin/resource-template/show-property-row');
    }
}
