<?php
namespace Omeka\Stdlib;

class Oembed
{
    protected $allowList;

    protected $httpClient;

    public function __construct(array $allowList, HttpClient $httpClient)
    {
        $this->allowList = $allowList;
        $this->httpClient = $httpClient;
    }

    // urlIsAllowed()
    // getResponse()
    // discoverLinks()
}
