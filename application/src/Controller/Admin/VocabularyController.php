<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\VocabularyReview;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class VocabularyController extends AbstractActionController
{
    /**
     * @var RdfImporter
     */
    protected $rdfImporter;

    /**
     * @var VocabularyReview
     */
    protected $vocabularyReview;

    /**
     * @param RdfImporter $rdfImporter
     * @param VocabularyReview $vocabularyReview
     */
    public function __construct(RdfImporter $rdfImporter, VocabularyReview $vocabularyReview)
    {
        $this->rdfImporter = $rdfImporter;
        $this->vocabularyReview = $vocabularyReview;
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
        if (!$vocabulary->userIsAllowed('update')) {
            throw new Exception\PermissionDeniedException('User does not have permission to edit this vocabulary');
        }
        // A new file is read as the vocabulary's was, unless changed here.
        $importOptions = $vocabulary->importOptions() ?? [];
        $data = [
            'vocabulary-info' => [
                'o:label' => $vocabulary->label(),
                'o:comment' => $vocabulary->comment(),
                'o:namespace_uri' => $vocabulary->namespaceUri(),
            ],
            'vocabulary-file' => array_intersect_key($importOptions, array_flip(['format'])),
            'vocabulary-advanced' => array_intersect_key($importOptions, array_flip(['lang', 'label_property', 'comment_property'])),
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
                $info = $data['vocabulary-info'];
                // With no file chosen, or the URL left empty, only the
                // vocabulary's own fields change. The form has already refused
                // a file that failed to upload, so an empty temp file name
                // means none was chosen.
                $file = $data['vocabulary-file']['file'] ?? [];
                $url = trim((string) $data['vocabulary-file']['url']);
                $upload = 'upload' === $data['vocabulary-file']['import_type'] && !empty($file['tmp_name']);
                $fetch = 'url' === $data['vocabulary-file']['import_type'] && '' !== $url;
                if (!$upload && !$fetch) {
                    // Where the API refuses the vocabulary's own fields, it
                    // puts its reasons on the form, which is shown again.
                    $response = $this->api($form)->update('vocabularies', $this->params('id'), $info, [], ['isPartial' => true]);
                    if ($response) {
                        $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                        return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
                    }
                } elseif ($this->isNamespaceUriTaken($info['o:namespace_uri'], $vocabulary)) {
                    // As the API would refuse it on accepting the changes.
                    $this->messenger()->addError(new Message(
                        'The namespace URI "%s" is already taken.', // @translate
                        trim($info['o:namespace_uri'])
                    ));
                } else {
                    // Nothing is saved until the changes are reviewed and
                    // accepted, the vocabulary's own fields with its terms'.
                    $reading = [
                        'format' => $data['vocabulary-file']['format'],
                        'lang' => $data['vocabulary-advanced']['lang'],
                        'label_property' => $data['vocabulary-advanced']['label_property'],
                        'comment_property' => $data['vocabulary-advanced']['comment_property'],
                    ];
                    $pending = [];
                    $label = trim((string) $info['o:label']);
                    if ($label !== $vocabulary->label()) {
                        $pending['label'] = $label;
                    }
                    if ((string) $info['o:comment'] !== (string) $vocabulary->comment()) {
                        $pending['comment'] = $info['o:comment'];
                    }
                    $namespaceUri = trim((string) $info['o:namespace_uri']);
                    if ($namespaceUri !== $vocabulary->namespaceUri()) {
                        $pending['namespace_uri'] = $namespaceUri;
                    }
                    try {
                        $reviewUrl = $upload
                            ? $this->vocabularyReview->start($vocabulary->id(), $file['tmp_name'], $reading, $pending)
                            : $this->vocabularyReview->startFromUrl($vocabulary->id(), $url, $reading, $pending);
                        return $this->redirect()->toUrl($reviewUrl);
                    } catch (\Exception $e) {
                        $this->messenger()->addErrors($this->getErrors($e));
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
     * Show the changes a review's file would make, read as the admin
     * chooses, for them to accept or cancel.
     */
    public function reviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
        }
        $reading = $this->vocabularyReview->getReading($review, $this->getChoices($this->params()->fromQuery()));

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('reading', $reading);
        $view->setVariable('form', $this->getForm(ConfirmForm::class));
        $view->setVariable('returnUrl', $this->vocabularyReview->getReturnUrl($review));
        try {
            $view->setVariable('diff', $this->vocabularyReview->getDiff($review, $reading));
            $view->setVariable('missing', $this->vocabularyReview->getMissingTerms($review, $reading));
            $view->setVariable('languages', $this->vocabularyReview->getLanguages($review, $reading));
        } catch (\Exception $e) {
            // Read another way, the file may not do; say why, and leave the
            // choices to change.
            $this->messenger()->addErrors($this->getErrors($e));
            $view->setVariable('diff', null);
            $view->setVariable('missing', null);
            $view->setVariable('languages', []);
        }
        return $view;
    }

    /**
     * Accept a review's changes, read as chosen on the review page.
     */
    public function acceptAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $post = $this->params()->fromPost();
        $token = (string) ($post['review'] ?? '');
        $review = $this->getRequest()->isPost() ? $this->getReview($token, $vocabulary) : null;
        if (!$review) {
            return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
        }
        $choices = $this->getChoices($post);
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        try {
            $this->vocabularyReview->accept($token, $this->vocabularyReview->getReading($review, $choices));
        } catch (ValidationException $e) {
            // Nothing was changed, and the review goes on.
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        $this->messenger()->addSuccess('Changes to the vocabulary successfully made'); // @translate
        return $this->redirect()->toUrl($this->vocabularyReview->getReturnUrl($review));
    }

    /**
     * Cancel a review, changing nothing.
     */
    public function cancelAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $post = $this->params()->fromPost();
        $token = (string) ($post['review'] ?? '');
        $review = $this->getRequest()->isPost() ? $this->getReview($token, $vocabulary) : null;
        if (!$review) {
            return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
        }
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($post);
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return $this->redirect()->toUrl($this->getReviewUrl($vocabulary, $token, $post));
        }
        $this->vocabularyReview->cancel($token);
        return $this->redirect()->toUrl($this->vocabularyReview->getReturnUrl($review));
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
            if ($vocabulary->isPermanent()) {
                throw new Exception\PermissionDeniedException('Cannot delete a permanent vocabulary');
            }
            if (!$vocabulary->userIsAllowed('delete')) {
                throw new Exception\PermissionDeniedException('User does not have permission to delete this vocabulary');
            }
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
        return $this->redirect()->toRoute('admin/default', ['action' => 'browse'], true);
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

    /**
     * Get the vocabulary of the request, refusing one the user may not
     * change: a permanent one, or another's.
     *
     * @return \Omeka\Api\Representation\VocabularyRepresentation
     */
    protected function getVocabularyToUpdate()
    {
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
        if ($vocabulary->isPermanent()) {
            throw new Exception\PermissionDeniedException('Cannot update a permanent vocabulary');
        }
        if (!$vocabulary->userIsAllowed('update')) {
            throw new Exception\PermissionDeniedException('User does not have permission to update this vocabulary');
        }
        return $vocabulary;
    }

    /**
     * Get a review of the vocabulary, saying why where there's none.
     *
     * @param string $token
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return array|null
     */
    protected function getReview($token, $vocabulary)
    {
        $review = $this->vocabularyReview->get($token);
        if (!$review || $review['vocabulary_id'] !== $vocabulary->id()) {
            $this->messenger()->addError('This review has ended. Edit the vocabulary to review its changes again.'); // @translate
            return null;
        }
        return $review;
    }

    /**
     * Get the reading options the admin chose on the review page: its
     * preferred language ("" for the first available) and where comments
     * are read from.
     *
     * @param array $params The page's query, or the post of its form
     * @return array
     */
    protected function getChoices(array $params)
    {
        $choices = [];
        if (isset($params['lang']) && is_string($params['lang'])) {
            $choices['lang'] = '' === $params['lang'] ? null : $params['lang'];
        }
        $commentProperties = [
            'comment' => RdfImporter::COMMENT_PROPERTY,
            'definition' => RdfImporter::DEFINITION_COMMENT_PROPERTY,
        ];
        if (isset($params['comments']) && is_string($params['comments']) && isset($commentProperties[$params['comments']])) {
            $choices['comment_property'] = $commentProperties[$params['comments']];
        }
        return $choices;
    }

    /**
     * Get the URL of a review's page, with the choices made on it.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param string $token
     * @param array $params
     * @return string
     */
    protected function getReviewUrl($vocabulary, $token, array $params)
    {
        $query = ['review' => $token];
        foreach (['lang', 'comments'] as $key) {
            if (isset($params[$key]) && is_string($params[$key])) {
                $query[$key] = $params[$key];
            }
        }
        return $this->url()->fromRoute('admin/id', [
            'controller' => 'vocabulary',
            'id' => $vocabulary->id(),
            'action' => 'review',
        ], ['query' => $query]);
    }

    /**
     * Check whether another vocabulary has a namespace URI.
     *
     * @param string $namespaceUri
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return bool
     */
    protected function isNamespaceUriTaken($namespaceUri, $vocabulary)
    {
        $namespaceUri = trim((string) $namespaceUri);
        if ($namespaceUri === $vocabulary->namespaceUri()) {
            return false;
        }
        return (bool) $this->api()->search('vocabularies', ['namespace_uri' => $namespaceUri])->getTotalResults();
    }

    /**
     * Get the messages of an exception, with those in its error store, where
     * the importer names the terms at fault.
     *
     * @param \Exception $e
     * @return array
     */
    protected function getErrors(\Exception $e)
    {
        $errors = [$e->getMessage()];
        if (method_exists($e, 'getErrorStore')) {
            $errors[] = $e->getErrorStore()->getErrors();
        }
        return $errors;
    }
}
