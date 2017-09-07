<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHtmlElement;

/**
 * View helper for building a hidden form input for every query in the URL query
 * string.
 */
class QueryToHiddenInputs extends AbstractHtmlElement
{
    /**
     * Build a hidden form input for every query in the URL query string.
     *
     * Used to preserve the current query string when submitting a GET form.
     *
     * @param array $removeQueries Remove these queries by name
     * @return string
     */
    public function __invoke(array $removeQueries = [])
    {
        $hiddenInputs = '';
        $view = $this->getView();
        $queries = explode('&', http_build_query($view->params()->fromQuery()));
        foreach ($queries as $query) {
            if (!$query) {
                continue;
            }
            list($queryName, $queryValue) = array_map('urldecode', explode('=', $query));
            if (in_array($queryName, $removeQueries)) {
                continue;
            }
            $hiddenInputs .= sprintf(
                '<input type="hidden" name="%s" value="%s">',
                $view->escapeHtml($queryName),
                $view->escapeHtml($queryValue)
            );
        }
        return $hiddenInputs;
    }
}
