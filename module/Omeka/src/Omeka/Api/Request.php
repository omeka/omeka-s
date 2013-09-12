<?php
namespace Omeka\Api;

class Request
{
    const METHOD_CREATE = 1;
    const METHOD_READ   = 2;
    const METHOD_UPDATE = 3;
    const METHOD_DELETE = 4;
    
    public function __construct($method, $resource, $id = null, $params = null, $payload = null)
    {
        
    }
}
