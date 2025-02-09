<?php

namespace Omeka\Mvc;

use Omeka\Api\Exception as ApiException;
use Omeka\Mvc\Exception as MvcException;
use Omeka\Permissions\Exception as AclException;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Response;
use Laminas\Mvc\Application as ZendApplication;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\ViewModel;

/**
 * MVC listener for handling dispatch and render exceptions.
 */
class ExceptionListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'handleException'], -5000);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'handleException'], -5000);
    }

    /**
     * Pass all exceptions that will display an error page to the logger.
     *
     * For dispatch exceptions, use custom error pages and status codes for
     * 403/404-type exceptions.
     *
     * @param MvcEvent $e
     */
    public function handleException(MvcEvent $e)
    {
        $result = $e->getResult();
        // Don't interfere with a complete response.
        if ($result instanceof ResponseInterface) {
            return;
        }

        // Only handle exceptions.
        if ($e->getError() !== ZendApplication::ERROR_EXCEPTION) {
            return;
        }

        // Only modify template and error code for dispatch errors
        if ($e->getName() === MvcEvent::EVENT_DISPATCH_ERROR) {
            $this->modifyResponse($e);
        }

        $exception = $e->getParam('exception');
        $e->getApplication()->getServiceManager()->get('Omeka\Logger')->err((string) $exception);
    }

    /**
     * Convert PermissionDenied and NotFound exceptions to 403/404 errors, with
     * unique templates for each.
     */
    private function modifyResponse(MvcEvent $e)
    {
        $exception = $e->getParam('exception');

        if ($exception instanceof AclException\PermissionDeniedException) {
            $template = 'error/403';
            $status = 403;
        } elseif ($exception instanceof ApiException\NotFoundException
            || $exception instanceof MvcException\NotFoundException) {
            $template = 'error/404';
            $status = 404;
        } else {
            return;
        }

        $model = new ViewModel([
            'exception' => $exception,
        ]);
        $model->setTemplate($template);

        $response = $e->getResponse();
        if (!$response) {
            $response = new Response;
        }
        $response->setStatusCode($status);
        $e->setResponse($response);
        $e->getViewModel()->addChild($model);
    }
}
