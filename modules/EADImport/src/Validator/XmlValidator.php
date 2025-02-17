<?php

namespace EADImport\Validator;

use XMLReader;

class XmlValidator
{
    /**
     * Formatted libxml Error details
     *
     * @var array
     */
    public $errorDetails;
    /**
     * Validation Class constructor Instantiating DOMDocument
     *
     * @param \DOMDocument $handler [description]
     */
    public function __construct()
    {
        $this->handler = new XMLReader;
    }
    /**
     * @param \libXMLError object $error
     *
     * @return string
     */
    private function libxmlDisplayError($error)
    {
        $errorString = "Error $error->code (Line:{$error->line}):";
        $errorString .= trim($error->message);
        return $errorString;
    }
    /**
     * @return array
     */
    private function libxmlDisplayErrors()
    {
        $errors = libxml_get_errors();
        $result = [];
        foreach ($errors as $error) {
            $result[] = $this->libxmlDisplayError($error);
        }
        return $result;
    }
    /**
     * Validate Incoming Feeds against Listing Schema
     *
     * @param resource $feeds
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function validateFeeds($feeds, $schemaPath)
    {
        $this->handler->open($feeds);
        $this->handler->setSchema($schemaPath);
        libxml_use_internal_errors(true);

        while ($this->handler->read()) {
            if (!$this->handler->isValid()) {
                $this->errorDetails = $this->libxmlDisplayErrors();
            } else {
                return true;
            }
        }
    }
    /**
     * Display Error if Resource is not validated
     *
     * @return array
     */
    public function displayErrors()
    {
        libxml_clear_errors();

        return $this->errorDetails;
    }
}
