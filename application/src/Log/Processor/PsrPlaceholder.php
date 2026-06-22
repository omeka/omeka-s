<?php
namespace Omeka\Log\Processor;

use Laminas\Log\Processor\ProcessorInterface;
use Omeka\Stdlib\PsrInterpolateTrait;

class PsrPlaceholder implements ProcessorInterface
{
    use PsrInterpolateTrait;

    public function process(array $event)
    {
        $event['message'] = $this->interpolate($event['message'], $event['extra']);
        return $event;
    }
}
