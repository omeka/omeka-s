<?php declare(strict_types=1);

namespace Common\Log\Formatter;

trait PsrLogAwareTrait
{
    /**
     * Normalize context data.
     *
     * To be used with the processors UserId and JobId.
     *
     * @param array $event event data
     * @return array
     */
    protected function normalizeLogContext(array $event)
    {
        if (empty($event['extra'])) {
            return $event;
        }

        $eventExtra = [];

        $message = $event['message'];
        $context = $event['extra'];
        if (strpos($message, '{userId}') === false) {
            unset($context['userId']);
        }
        if (strpos($message, '{jobId}') === false) {
            unset($context['jobId']);
        }
        if (strpos($message, '{referenceId}') === false) {
            unset($context['referenceId']);
        }
        if ($context) {
            // Check if there are non-mappable extra data.
            $missingPlaceholders = array_filter($context, fn ($key) => strpos($message, '{' . $key . '}') === false, ARRAY_FILTER_USE_KEY);
            if ($missingPlaceholders) {
                if (strpos($message, '{extra}') === false) {
                    $event['message'] .= ' {extra}';
                }
                $context['extra'] = $missingPlaceholders;
            }
            $eventExtra['context'] = $context;
        }

        if (!empty($event['extra']['userId'])) {
            $userId = (int) $event['extra']['userId'];
            if (!empty($userId)) {
                $eventExtra['userId'] = $userId;
            }
        }

        if (!empty($event['extra']['jobId'])) {
            $jobId = (int) $event['extra']['jobId'];
            if (!empty($jobId)) {
                $eventExtra['jobId'] = $jobId;
            }
        }

        if (!empty($event['extra']['referenceId'])) {
            $eventExtra['referenceId'] = $event['extra']['referenceId'];
        }

        $event['extra'] = $eventExtra;

        return $event;
    }
}
