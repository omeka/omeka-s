<?php declare(strict_types=1);

namespace Common\Validator;

use Laminas\Validator\AbstractValidator;

class ReadableDirectory extends AbstractValidator
{
    const NOT_RAW = 'containsDoubleDots';
    const NOT_EXISTS = 'notDirectory';
    const NOT_DIRECTORY = 'notDirectory';
    const NOT_READABLE = 'notReadable';
    const NOT_IN_BASE_PATH = 'notInPath';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        self::NOT_RAW => 'The path should not contain double dot or be hidden.', // @translate
        self::NOT_EXISTS => 'The path does not exist.', // @translate
        self::NOT_DIRECTORY => 'The path is not a directory', // @translate
        self::NOT_READABLE => 'The path is not readable', // @translate
        self::NOT_IN_BASE_PATH => 'The path is not inside the restricted path', // @translate
    ];

    public function isValid($value)
    {
        if (empty($value)) {
            return false;
        }

        $this->setValue((string) $value);

        $path = $value;

        if (strpos($value, '../') !== false || strpos($value, '/.') !== false) {
            $this->error(self::NOT_RAW);
            return false;
        }

        try {
            $basePath = $this->getOption('base_path');
            if (strlen($basePath)) {
                if (strpos($value, $basePath) !== 0) {
                    $this->error(self::NOT_IN_BASE_PATH);
                    return false;
                }
            }
        } catch (\Laminas\Validator\Exception\InvalidArgumentException $e) {
        }

        if (!file_exists($path)) {
            $this->error(self::NOT_EXISTS);
            return false;
        }

        $path = $value;
        if (!is_dir($path)) {
            $this->error(self::NOT_DIRECTORY);
            return false;
        }

        $path = $value;
        if (!is_readable($path)) {
            $this->error(self::NOT_READABLE);
            return false;
        }

        return true;
    }
}
