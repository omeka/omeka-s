<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
use Omeka\Stdlib\NamespaceUri;
use Omeka\Stdlib\VocabularyFileStore;
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
     * @var VocabularyFileStore
     */
    protected $fileStore;

    /**
     * @param RdfImporter $rdfImporter
     * @param VocabularyReview $vocabularyReview
     * @param VocabularyFileStore $fileStore
     */
    public function __construct(RdfImporter $rdfImporter, VocabularyReview $vocabularyReview,
        VocabularyFileStore $fileStore
    ) {
        $this->rdfImporter = $rdfImporter;
        $this->vocabularyReview = $vocabularyReview;
        $this->fileStore = $fileStore;
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

    /**
     * Show a vocabulary, the page its other pages come back to.
     */
    public function showAction()
    {
        $view = new ViewModel;
        $view->setVariable('vocabulary', $this->api()->read('vocabularies', $this->params('id'))->getContent());
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
        $view->setVariable('partialPath', 'omeka/admin/vocabulary/delete-confirm-details');
        return $view;
    }

    /**
     * Import a vocabulary, first step: keep its file and go on to its
     * details.
     */
    public function importAction()
    {
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'import_file']);
        $source = $this->getRequest()->isPost() ? $this->getPostedFileSource($form) : null;
        if ($source) {
            $token = null;
            try {
                $token = isset($source['file'])
                    ? $this->fileStore->keep($source['file'])
                    : $this->fileStore->keepFromUrl($source['url']);
                // Refuse a file that can't be read, or has no terms, now.
                $this->getImportDetails($token, $source['format']);
                return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import-details'], [
                    'query' => ['file' => $token, 'format' => $source['format']],
                ]);
            } catch (\Exception $e) {
                if (null !== $token) {
                    $this->fileStore->remove($token);
                }
                $this->messenger()->addErrors($this->getErrors($e));
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Import a vocabulary, second step: its fields and how its file is read,
     * starting as the file says. The query gives the file's token and format.
     */
    public function importDetailsAction()
    {
        $token = (string) $this->params()->fromQuery('file');
        $format = (string) $this->params()->fromQuery('format');
        if (!in_array($format, ['guess', 'jsonld', 'ntriples', 'rdfxml', 'turtle'], true)) {
            $format = 'guess';
        }
        $path = $this->fileStore->getPath($token);
        if (null === $path) {
            $this->messenger()->addError('This import has ended. Choose the file again.'); // @translate
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        try {
            $details = $this->getImportDetails($token, $format);
        } catch (\Exception $e) {
            $this->fileStore->remove($token);
            $this->messenger()->addErrors($this->getErrors($e));
            return $this->redirect()->toRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import']);
        }
        $form = $this->getForm(VocabularyForm::class, ['mode' => 'import_details', 'languages' => $details['languages']]);
        $form->setData([
            'vocabulary-info' => [
                'o:label' => $details['label'],
                'o:comment' => $details['comment'],
                'o:namespace_uri' => $details['namespace_uri'],
                'o:prefix' => $details['prefix'],
            ],
            'vocabulary-advanced' => [
                'lang' => $this->getDefaultLanguage($details['languages']),
                'comments' => 'comment',
            ],
        ]);
        $request = $this->getRequest();
        // The namespace URI found may be another vocabulary's, as when a
        // file redeclares terms it reuses.
        if (!$request->isPost() && null !== $details['namespace_uri']) {
            $other = $this->getVocabularyWithNamespace($details['namespace_uri']);
            if ($other) {
                $this->messenger()->addWarning($this->getAlreadyHasMessage($other, true));
            }
        }
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $data = $form->getData();
                // Imported again, in any form of its namespace, a
                // vocabulary's terms would be twice.
                $other = $this->getVocabularyWithNamespace($data['vocabulary-info']['o:namespace_uri']);
                if ($other) {
                    $this->messenger()->addError($this->getAlreadyHasMessage($other));
                } else {
                    $advanced = $data['vocabulary-advanced'];
                    $options = [
                        'file' => $path,
                        'format' => $format,
                        'lang' => '' === (string) $advanced['lang'] ? null : $advanced['lang'],
                        'label_property' => $advanced['label_property'],
                        'comment_property' => $this->getChosenCommentProperty($advanced),
                    ];
                    try {
                        $response = $this->rdfImporter->import('file', $data['vocabulary-info'], $options);
                        $this->fileStore->remove($token);
                        $message = new Message(
                            'Vocabulary successfully imported. %s', // @translate
                            sprintf(
                                '<a href="%s">%s</a>',
                                htmlspecialchars($this->url()->fromRoute('admin/default', ['controller' => 'vocabulary', 'action' => 'import'])),
                                $this->translate('Import another vocabulary?')
                            )
                        );
                        $message->setEscapeHtml(false);
                        $this->messenger()->addSuccess($message);
                        return $this->redirect()->toUrl($response->getContent()->url());
                    } catch (\Exception $e) {
                        $this->messenger()->addErrors($this->getErrors($e));
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('details', $details);
        return $view;
    }

    /**
     * Edit a vocabulary's label, comment, and namespace URI.
     */
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
        $form->setData([
            'vocabulary-info' => [
                'o:label' => $vocabulary->label(),
                'o:comment' => $vocabulary->comment(),
                'o:namespace_uri' => $vocabulary->namespaceUri(),
            ],
        ]);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());
            if ($form->isValid()) {
                $info = $form->getData()['vocabulary-info'];
                // Refuse another form of another vocabulary's namespace URI,
                // as an import does.
                $other = $info['o:namespace_uri'] === $vocabulary->namespaceUri()
                    ? null
                    : $this->getVocabularyInAnotherForm($info['o:namespace_uri'], $vocabulary);
                if ($other) {
                    $this->messenger()->addError(new Message(
                        'Another vocabulary, "%1$s", has this namespace URI in another form: "%2$s".', // @translate
                        $other->label(),
                        $other->namespaceUri()
                    ));
                } else {
                    // The API puts its refusals on the form.
                    $response = $this->api($form)->update('vocabularies', $this->params('id'), $info, [], ['isPartial' => true]);
                    if ($response) {
                        $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                        return $this->redirect()->toUrl($response->getContent()->url());
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
     * Update a vocabulary from a newer version of its file, by way of a
     * review.
     */
    public function updateAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary, 'mode' => 'update_from_file']);
        $format = $vocabulary->importOptions()['format'] ?? null;
        if (null !== $format) {
            $form->setData(['vocabulary-file' => ['format' => $format]]);
        }
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $source = $this->getRequest()->isPost() ? $this->getPostedFileSource($form) : null;
        if ($source) {
            $reading = ['format' => $source['format']];
            try {
                $reviewUrl = isset($source['file'])
                    ? $this->vocabularyReview->start($vocabulary->id(), $source['file'], $reading)
                    : $this->vocabularyReview->startFromUrl($vocabulary->id(), $source['url'], $reading);
                return $this->redirect()->toUrl($reviewUrl);
            } catch (\Exception $e) {
                $namespaceUri = $this->getFileNamespaceUri($e);
                if (null === $namespaceUri) {
                    $this->messenger()->addErrors($this->getErrors($e));
                } else {
                    // Both namespaces, which may differ by a character.
                    $message = new Message(
                        'None of this file\'s classes or properties are in this vocabulary\'s namespace, "%1$s". They use "%2$s". If the vocabulary has moved, change its namespace, then update it again.', // @translate
                        $vocabulary->namespaceUri(),
                        $namespaceUri
                    );
                    $this->messenger()->addError($message);
                }
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Show the changes a review's file would make, read as the admin chooses.
     */
    public function reviewAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $token = (string) $this->params()->fromQuery('review');
        $review = $this->getReview($token, $vocabulary);
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
        }
        $reading = $this->vocabularyReview->getReading($review, $this->getChoices($this->params()->fromQuery()));

        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $view->setVariable('review', $review);
        $view->setVariable('token', $token);
        $view->setVariable('reading', $reading);
        $view->setVariable('form', $this->getForm(ConfirmForm::class));
        try {
            $view->setVariable('diff', $this->vocabularyReview->getDiff($review, $reading));
            $view->setVariable('missing', $this->vocabularyReview->getMissingTerms($review, $reading));
            $view->setVariable('languages', $this->vocabularyReview->getLanguages($review, $reading));
        } catch (\Exception $e) {
            // Read another way, the file may fail; the choices stay to change.
            $this->messenger()->addErrors($this->getErrors($e));
            $view->setVariable('diff', null);
            $view->setVariable('missing', null);
            $view->setVariable('languages', []);
        }
        return $view;
    }

    /**
     * Accept a review's changes.
     */
    public function acceptAction()
    {
        $vocabulary = $this->getVocabularyToUpdate();
        $post = $this->params()->fromPost();
        $token = (string) ($post['review'] ?? '');
        $review = $this->getRequest()->isPost() ? $this->getReview($token, $vocabulary) : null;
        if (!$review) {
            return $this->redirect()->toUrl($vocabulary->url());
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
            return $this->redirect()->toUrl($vocabulary->url());
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
     * change.
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
     * Get a review of the vocabulary, or say it has ended.
     *
     * @param string $token
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @return array|null
     */
    protected function getReview($token, $vocabulary)
    {
        $review = $this->vocabularyReview->get($token);
        if (!$review || $review['vocabulary_id'] !== $vocabulary->id()) {
            $this->messenger()->addError('This review has ended. Update the vocabulary\'s classes and properties to review its changes again.'); // @translate
            return null;
        }
        return $review;
    }

    /**
     * Get the reading options chosen on the review page.
     *
     * @param array $params The page's query, or its form's post
     * @return array
     */
    protected function getChoices(array $params)
    {
        $choices = [];
        if (isset($params['lang']) && is_string($params['lang'])) {
            $choices['lang'] = '' === $params['lang'] ? null : $params['lang'];
        }
        if (isset($params['label_property']) && is_string($params['label_property'])) {
            $choices['label_property'] = '' === trim($params['label_property']) ? null : trim($params['label_property']);
        }
        $commentProperty = $this->getChosenCommentProperty($params);
        if (null !== $commentProperty) {
            $choices['comment_property'] = $commentProperty;
        }
        return $choices;
    }

    /**
     * Get the comment property chosen: a reading offered, or another entered.
     * Another with none entered is no choice.
     *
     * @param array $params With "comments" and "comment_property"
     * @return string|null
     */
    protected function getChosenCommentProperty(array $params)
    {
        $comments = $params['comments'] ?? null;
        if (!is_string($comments)) {
            return null;
        }
        if ('other' === $comments) {
            $property = is_string($params['comment_property'] ?? null) ? trim($params['comment_property']) : '';
            return '' === $property ? null : $property;
        }
        return RdfImporter::COMMENT_READINGS[$comments] ?? null;
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
        foreach (['lang', 'comments', 'label_property', 'comment_property'] as $key) {
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
     * Get the file a posted form chooses, saying why where there's none.
     *
     * @param VocabularyForm $form
     * @return array|null file (an upload's path) or url, and format
     */
    protected function getPostedFileSource(VocabularyForm $form)
    {
        $request = $this->getRequest();
        $form->setData(array_merge_recursive(
            $request->getPost()->toArray(),
            $request->getFiles()->toArray()
        ));
        if (!$form->isValid()) {
            $this->messenger()->addFormErrors($form);
            return null;
        }
        $data = $form->getData()['vocabulary-file'];
        // The form has refused a failed upload, so no temp name means none.
        $file = $data['file'] ?? [];
        $url = trim((string) ($data['url'] ?? ''));
        if ('upload' === $data['import_type'] && !empty($file['tmp_name'])) {
            return ['file' => $file['tmp_name'], 'format' => $data['format']];
        }
        if ('url' === $data['import_type'] && '' !== $url) {
            return ['url' => $url, 'format' => $data['format']];
        }
        $this->messenger()->addError('Choose a file or enter a URL.'); // @translate
        return null;
    }

    /**
     * Get what a kept file says of its vocabulary.
     *
     * @see RdfImporter::getVocabularyDetails()
     * @param string $token
     * @param string $format
     * @return array
     */
    protected function getImportDetails($token, $format)
    {
        return $this->rdfImporter->getVocabularyDetails('file', [
            'file' => $this->fileStore->getPath($token),
            'format' => $format,
            'lang' => strtok($this->getLocaleLanguage(), '-') ?: null,
        ]);
    }

    /**
     * Get the default language to read a file's terms in: the installation's,
     * else English, else the first available ("").
     *
     * @param string[] $languages The file's languages
     * @return string
     */
    protected function getDefaultLanguage(array $languages)
    {
        $locale = $this->getLocaleLanguage();
        $primary = strtok($locale, '-');
        // Each as its tag (en-us) or language (en), before another region
        // (en-GB).
        foreach (array_unique([$primary, 'en']) as $wanted) {
            $tags = $wanted === $primary ? array_unique([$locale, $primary]) : [$wanted];
            foreach ($tags as $tag) {
                foreach ($languages as $language) {
                    if (strtolower($language) === $tag) {
                        return $language;
                    }
                }
            }
            foreach ($languages as $language) {
                if (0 === strpos(strtolower($language), $wanted . '-')) {
                    return $language;
                }
            }
        }
        return '';
    }

    /**
     * Get the installation's language as a tag, such as "en-us".
     *
     * @return string
     */
    protected function getLocaleLanguage()
    {
        return strtolower(str_replace('_', '-', (string) ($this->settings()->get('locale') ?: 'en')));
    }

    /**
     * Get a vocabulary with a namespace URI, in any form.
     *
     * @param string $namespaceUri
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyWithNamespace($namespaceUri)
    {
        return $this->api()->searchOne('vocabularies', ['namespace_uri' => NamespaceUri::getForms($namespaceUri)])->getContent();
    }

    /**
     * Get the message that Omeka already has a vocabulary being imported.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation $vocabulary
     * @param bool $found Whether its namespace URI is the one found in the
     *   file, rather than given
     * @return Message
     */
    protected function getAlreadyHasMessage($vocabulary, $found = false)
    {
        $mayUpdate = !$vocabulary->isPermanent() && $vocabulary->userIsAllowed('update');
        $link = sprintf('<a href="%s">%s</a>', htmlspecialchars($vocabulary->url()), htmlspecialchars($vocabulary->label()));
        $namespaceUri = htmlspecialchars($vocabulary->namespaceUri());
        if ($found) {
            $message = new Message(
                $mayUpdate
                    ? 'Omeka already has a vocabulary with the namespace URI this file appears to use, "%1$s": %2$s. If the file is that vocabulary, update it from its page instead. If not, enter the file\'s own namespace URI below.' // @translate
                    : 'Omeka already has a vocabulary with the namespace URI this file appears to use, "%1$s": %2$s. If the file is a different vocabulary, enter its own namespace URI below.', // @translate
                $namespaceUri,
                $link
            );
        } else {
            $message = new Message(
                $mayUpdate
                    ? 'Omeka already has this vocabulary, as "%1$s" with the namespace URI "%2$s". To update it, use Update classes and properties on its page.' // @translate
                    : 'Omeka already has this vocabulary, as "%1$s" with the namespace URI "%2$s".', // @translate
                $link,
                $namespaceUri
            );
        }
        $message->setEscapeHtml(false);
        return $message;
    }

    /**
     * Get a vocabulary whose namespace URI is another form of one.
     *
     * @param string $namespaceUri
     * @param \Omeka\Api\Representation\VocabularyRepresentation $except
     * @return \Omeka\Api\Representation\VocabularyRepresentation|null
     */
    protected function getVocabularyInAnotherForm($namespaceUri, $except)
    {
        $vocabularies = $this->api()->search('vocabularies', ['namespace_uri' => NamespaceUri::getForms($namespaceUri)])->getContent();
        foreach ($vocabularies as $vocabulary) {
            if ($vocabulary->namespaceUri() !== $namespaceUri && $vocabulary->id() !== $except->id()) {
                return $vocabulary;
            }
        }
        return null;
    }

    /**
     * Get the namespace URI the importer found a refused file's terms use.
     *
     * @see RdfImporter::checkNamespaceUri()
     * @param \Exception $e
     * @return string|null
     */
    protected function getFileNamespaceUri(\Exception $e)
    {
        if (!$e instanceof ValidationException) {
            return null;
        }
        foreach ($e->getErrorStore()->getErrors()['o:namespace_uri'] ?? [] as $message) {
            if ($message instanceof Message && $message->hasArgs()) {
                return (string) $message->getArgs()[0];
            }
        }
        return null;
    }

    /**
     * Get the messages of an exception, with those in its error store, or
     * else that something went wrong.
     *
     * @param \Exception $e
     * @return array
     */
    protected function getErrors(\Exception $e)
    {
        // An API refusal has no message of its own, only its reasons.
        $errors = '' === (string) $e->getMessage() ? [] : [$e->getMessage()];
        if (method_exists($e, 'getErrorStore') && $e->getErrorStore()->getErrors()) {
            $errors[] = $e->getErrorStore()->getErrors();
        }
        return $errors ?: ['Something went wrong']; // @translate
    }
}
