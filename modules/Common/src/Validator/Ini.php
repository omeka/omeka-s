<?php declare(strict_types=1);

namespace Common\Validator;

use Laminas\Config\Exception as ConfigException;
use Laminas\Config\Reader\Ini as IniReader;
use Laminas\Validator\AbstractValidator;

/**
 * Check if a string is a valid ini that can be converted into an array.
 *
 * @see https://www.php.net/parse_ini_file
 *
 * @uses \Laminas\Config\Reader\Ini
 */
class Ini extends AbstractValidator
{
    public const INI_EXCEPTION = 'iniException';
    public const INCORRECT = 'iniIncorrect';
    public const INVALID = 'iniInvalid';

    /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $messageTemplates = [
        self::INI_EXCEPTION => 'The string is not formatted as ini', // @translate
        self::INCORRECT => 'The string is incorrectly formatted', // @translate
        self::INVALID => 'Invalid type given. String expected', // @translate
    ];

    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        try {
            $reader = new IniReader();
            $reader->fromString((string) $value);
            return true;
        } catch (ConfigException\RuntimeException $e) {
            $this->messageTemplates[self::INI_EXCEPTION] = $e->getMessage();
            $this->error(self::INI_EXCEPTION);
            return false;
        } catch (\Exception $e) {
            $this->error(self::INCORRECT);
            return false;
        }
    }
}
