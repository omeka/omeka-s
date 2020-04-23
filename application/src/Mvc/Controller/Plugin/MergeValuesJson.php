<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Mvc\Exception;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting a form from the form element manager.
 */
class MergeValuesJson extends AbstractPlugin
{
    /**
     * Merge separated JSON-encoded value data back into the main data array.
     *
     * @param array $data POST data
     * @return array
     */
    public function __invoke($data)
    {
        // Don't even try to continue if the values are missing, throw an error
        // instead of possibly deleting data because of a client-side error
        if (!isset($data['values_json']) || !$data['values_json']) {
            throw new Exception\RuntimeException('No JSON values data found in input.');
        }
        $jsonData = json_decode($data['values_json'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\InvalidJsonException('JSON error: ' . json_last_error_msg());
        }
        unset($data['values_json']);
        return array_merge($data, $jsonData);
    }
}
