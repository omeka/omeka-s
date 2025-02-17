<?php declare(strict_types=1);

namespace Common\Log\Formatter;

use Common\Stdlib\PsrInterpolateInterface;
use Common\Stdlib\PsrInterpolateTrait;
use Laminas\Log\Formatter\Simple;

class PsrLogSimple extends Simple implements PsrInterpolateInterface
{
    use PsrLogAwareTrait;
    use PsrInterpolateTrait;

    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param array $event event data
     * @return string formatted line to write to the log
     */
    public function format($event)
    {
        $output = $this->format;

        $event = $this->formatBase($event);
        $event = $this->normalizeLogContext($event);

        if (!empty($event['extra']['context']['extra'])) {
            $event['extra']['context']['extra'] = json_encode($event['extra']['context']['extra'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (!empty($event['extra']['context'])) {
            $event['message'] = $this->interpolate($event['message'], $event['extra']['context']);
        }
        unset($event['extra']['context']);

        $event = $this->formatBase($event);

        foreach ($event as $name => $value) {
            if ('extra' === $name && count($value)) {
                $value = $this->normalize($value);
            } elseif ('extra' === $name) {
                // Don't print an empty array
                $value = '';
            }
            $output = str_replace("%$name%", (string) $value, $output);
        }

        if (isset($event['extra']) && empty($event['extra'])
            && false !== strpos($this->format, '%extra%')
        ) {
            $output = rtrim($output, ' ');
        }
        return $output;
    }

    /**
     * Recursively format the context.
     *
     * @see \Laminas\Log\Formatter\Base::format().
     * @param array $event
     * @return array
     */
    protected function formatBase($event)
    {
        foreach ($event as $key => $value) {
            // Keep extra as an array
            if ('extra' === $key && is_array($value)) {
                $event[$key] = self::formatBase($value);
            } else {
                $event[$key] = $this->normalize($value);
            }
        }
        return $event;
    }
}
