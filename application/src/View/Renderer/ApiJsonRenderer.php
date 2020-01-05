<?php
namespace Omeka\View\Renderer;

use JsonSerializable;
use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Response;
use Omeka\Stdlib\JsonUnescaped as Json;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Exception;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ModelInterface as Model;
use Zend\View\Renderer\JsonRenderer;

/**
 * JSON renderer for API responses.
 */
class ApiJsonRenderer extends JsonRenderer
{
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

        $jsonpCallback = $model->getOption('callback');
        if (null !== $jsonpCallback) {
            // Wrap the JSON in a JSONP callback.
            $this->setJsonpCallback($jsonpCallback);
        }

        // Copy of \Zend\Json\Json::render(), but with JsonUnescaped as encoding
        // method in order to use a full pretty print if wanted, in one step.

        $nameOrModel = &$payload;
        $values = null;
        $prettyPrint = null !== $model->getOption('pretty_print');

        // use case 1: View Models
        // Serialize variables in view model
        if ($nameOrModel instanceof Model) {
            if ($nameOrModel instanceof JsonModel) {
                $children = $this->recurseModel($nameOrModel, false);
                $this->injectChildren($nameOrModel, $children);
                $values = $nameOrModel->serialize();
                // Pretty print the JSON for json serialized outside.
                if ($prettyPrint) {
                    $values = Json::prettyPrint($values);
                }
            } else {
                $values = $this->recurseModel($nameOrModel);
                $values = Json::encode($values, false, ['prettyPrint' => $prettyPrint]);
            }

            if ($this->hasJsonpCallback()) {
                $values = $this->jsonpCallback . '(' . $values . ');';
            }
            return $values;
        }

        // use case 2: $nameOrModel is populated, $values is not
        // Serialize $nameOrModel
        if (null === $values) {
            if (! is_object($nameOrModel) || $nameOrModel instanceof JsonSerializable) {
                $return = Json::encode($nameOrModel, false, ['prettyPrint' => $prettyPrint]);
            } elseif ($nameOrModel instanceof Traversable) {
                $nameOrModel = ArrayUtils::iteratorToArray($nameOrModel);
                $return = Json::encode($nameOrModel, false, ['prettyPrint' => $prettyPrint]);
            } else {
                $return = Json::encode(get_object_vars($nameOrModel), false, ['prettyPrint' => $prettyPrint]);
            }

            if ($this->hasJsonpCallback()) {
                $return = $this->jsonpCallback . '(' . $return . ');';
            }
            return $return;
        }

        // use case 3: Both $nameOrModel and $values are populated
        throw new Exception\DomainException(sprintf(
            '%s: Do not know how to handle operation when both $nameOrModel and $values are populated',
            __METHOD__
        ));
    }
}
