<?php
namespace Omeka\View\Renderer;

use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Response;
use Laminas\EventManager\EventManager;
use Laminas\Json\Json;
use Laminas\View\Renderer\JsonRenderer;

/**
 * JSON renderer for API responses.
 */
class ApiJsonRenderer extends JsonRenderer
{
    /**
     * @var bool
     */
    protected $hasJsonpCallback = false;

    /**
     * @var string The output format
     */
    protected $format;

    protected $eventManager;

    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Return whether the response is JSONP
     *
     * The view strategy checks this to decide what Content-Type to send, and
     * we need to provide a different implementation to preserve that signal
     * since we're handling JSONP manually here.
     *
     * @return bool
     */
    public function hasJsonpCallback()
    {
        return $this->hasJsonpCallback;
    }

    public function setHasJsonpCallback(bool $hasJsonpCallback)
    {
        $this->hasJsonpCallback = $hasJsonpCallback;
    }

    public function render($model, $values = null)
    {
        $response = $model->getApiResponse();
        $exception = $model->getException();

        if ($response instanceof Response) {
            $payload = $response->getContent();
        } elseif ($exception instanceof ValidationException) {
            $errors = $exception->getErrorStore()->getErrors();
            $payload = ['errors' => $errors];
        } elseif ($exception instanceof \Exception) {
            $payload = ['errors' => ['error' => $exception->getMessage()]];
        } else {
            $payload = $response;
        }

        if (null === $payload) {
            return null;
        }

        $output = parent::render($payload);

        // Allow modules to return custom output.
        $args = $this->eventManager->prepareArgs([
            'model' => $model,
            'payload' => $payload,
            'format' => $this->format,
            'output' => $output,
        ]);
        $this->eventManager->trigger('api.output.serialize', $this, $args);
        return $args['output'];
    }

    /**
     * Set an alternate output format.
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }
}
