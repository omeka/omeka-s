<?php
namespace Collecting\MediaType;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Laminas\Form\Form;
use Laminas\Stdlib\RequestInterface;
use Laminas\View\Renderer\PhpRenderer;

class UploadMultiple implements MediaTypeInterface
{
    protected $request;

    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    public function getLabel()
    {
        return 'Upload Multiple'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
    }

    public function form(Form $form, CollectingPromptRepresentation $prompt, $name)
    {
        // A hidden element marks the existence of this prompt.
        $form->add([
            'type' => 'hidden',
            'name' => $name,
        ]);
        // Note that the file index maps to the prompt ID.
        $form->add([
            'type' => 'file',
            'name' => sprintf('file[%s]', $prompt->id()),
            'options' => [
                'label' => $prompt->text(),
            ],
            'attributes' => [
                'required' => $prompt->required(),
                'multiple' => true,
            ],
        ]);
    }

    public function itemData(array $itemData, $postedPrompt,
        CollectingPromptRepresentation $prompt
    ) {
        $files = $this->request->getFiles('file');
        if (isset($files[$prompt->id()]) && is_array($files[$prompt->id()])) {
            foreach ($files[$prompt->id()] as $fileIndex => $file) {
                if ($prompt->required() || (!$prompt->required() && UPLOAD_ERR_NO_FILE !== $file['error'])) {
                    $mediaIndex = sprintf('%s-%s', $prompt->id(), $fileIndex);
                    $files[$mediaIndex] = $file;
                    $itemData['o:media'][$mediaIndex] = [
                        'o:ingester' => 'upload',
                        'file_index' => $mediaIndex,
                    ];
                }
            }
        }
        $this->request->getFiles()->set('file', $files);
        return $itemData;
    }
}
