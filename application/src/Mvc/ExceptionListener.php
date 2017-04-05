<?php

namespace Omeka\Mvc;

use Omeka\Api\Exception as ApiException;
use Omeka\Mvc\Exception as MvcException;
use Omeka\Permissions\Exception as AclException;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Response;
use Zend\Mvc\Application as ZendApplication;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\ResponseInterface;
use Zend\View\Model\ViewModel;

/**
 * MVC listener for handling specific exception types with "pretty" pages.
 */
class ExceptionListener extends AbstractListenerAggregate
{
    /**
     * {@inheritDoc}
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'handleException'], -5000);
    }

    /**
     * Listen for specific thrown exceptions and display the proper error page
     * and code for each.
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

        $exception = $e->getParam('exception');
        $e->getApplication()->getServiceManager()->get('Omeka\Logger')->err((string) $exception);

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
