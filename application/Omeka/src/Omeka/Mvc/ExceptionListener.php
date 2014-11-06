<?php

namespace Omeka\Mvc;

use Omeka\Api\Exception as ApiException;
use Omeka\Mvc\Exception as MvcException;
use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Response;
use Zend\Mvc\Application;
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
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, array($this, 'handleException'), -5000);
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
        if ($e->getError() !== Application::ERROR_EXCEPTION) {
            return;
        }

        $exception = $e->getParam('exception');

        if ($exception instanceof ApiException\PermissionDeniedException
            || $exception instanceof MvcException\PermissionDeniedException) {
            $template = 'error/403';
            $status = 403;
        } else if ($exception instanceof ApiException\NotFoundException) {
            $template = 'error/404';
            $status = 404;
        } else {
            return;
        }

        $model = new ViewModel(array(
            'exception' => $exception,
        ));
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
