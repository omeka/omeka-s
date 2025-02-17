<?php declare(strict_types=1);

namespace Sparql\Controller;

use Common\Stdlib\PsrMessage;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SparqlController extends AbstractActionController
{
    /**
     * @todo Manage sparql query fully. See https://github.com/semsol/arc2/wiki/SPARQL-Endpoint-Setup
     */
    public function sparqlAction()
    {
        $settings = $this->settings();

        $endpoint = $settings->get('sparql_endpoint');
        if ($endpoint === 'none' || $endpoint === 'external') {
            return $this->notFoundAction();
        } elseif ($endpoint === 'auto') {
            // Check if there is an external endpoint.
            $external = $settings->get('sparql_endpoint_external');
            if ($external) {
                return $this->notFoundAction();
            }
        }

        $sparqlSearch = $this->viewHelpers()->get('sparqlSearch');
        $result = $sparqlSearch([
            'sparqlArray' => true,
        ]);
        if (!$result) {
            $message = new PsrMessage('The RDF triplestore is not available currently.'); // @translate
            $this->messenger()->addError($message);
            return (new ViewModel())
                ->setTemplate('sparql/index/error');
        }

        $query = $result['query'];
        if (!$query) {
            // The arc2 endpoint manages format differently and the protocol
            // uses content negotiation (v1.1).
            /** @var \Sparql\Form\SparqlForm $form */
            $form = $result['form'];
            $form->remove('format');
            $result['interface'] = 'default';
            return new ViewModel($result);
        }

        /**
         * Convert arc2 response into a laminas response.
         *
         * @var \ARC2_StoreEndpoint $triplestore
         * @var \Laminas\Http\Response $response
         * @var \Laminas\Http\Headers $headers
         */
        $triplestore = $result['triplestore'];
        $content = $triplestore->getResult();
        $arc2Headers = $triplestore->headers;

        // Don't use mb_strlen.
        $arc2Headers['content-length'] = 'Content-Length: ' . strlen($content);
        // $arc2Headers['content-disposition'] = 'Content-Disposition: ' . sprintf('attachment; filename="%s.srx"', $filename);
        $statusCode = (int) substr($arc2Headers['http'], 9, 3);
        unset($arc2Headers['http']);
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        // $headers->addHeaders(array_values($arc2Headers));
        foreach ($arc2Headers as $headerLine) {
            $headers->addHeaderLine($headerLine);
        }

        $response
            ->setStatusCode($statusCode)
            ->setContent($content);
        return $response;
    }
}
