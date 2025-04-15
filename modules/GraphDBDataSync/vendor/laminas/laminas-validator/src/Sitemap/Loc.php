<?php

declare(strict_types=1);

namespace Laminas\Validator\Sitemap;

use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Uri;

use function html_entity_decode;
use function htmlentities;
use function is_string;
use function str_replace;
use function strlen;

/**
 * Validates whether a given value is valid as a sitemap <loc> value
 *
 * @link https://www.sitemaps.org/protocol.html Sitemaps XML format
 */
final class Loc extends AbstractValidator
{
    private const URI_MAX_LENGTH = 2048;

    public const NOT_VALID = 'sitemapLocNotValid';
    public const INVALID   = 'sitemapLocInvalid';
    public const TOO_LONG  = 'sitemapLocTooLong';

    /**
     * Validation failure message template definitions
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_VALID => 'The input is not a valid sitemap location',
        self::INVALID   => 'Invalid type given. String expected',
        self::TOO_LONG  => 'Sitemap URIs cannot be greater than 2048 characters',
    ];

    /**
     * Validates if a string is valid as a sitemap location
     *
     * @link https://www.sitemaps.org/protocol.html#locdef
     */
    public function isValid(mixed $value): bool
    {
        if (! is_string($value) || $value === '') {
            $this->error(self::INVALID);
            return false;
        }

        $validator = new Uri([
            'allowAbsolute' => true,
            'allowRelative' => false,
        ]);

        if (! $validator->isValid($value)) {
            $this->error(self::NOT_VALID);

            return false;
        }

        if ($this->containsUnEncodedHtmlEntities($value)) {
            $this->error(self::NOT_VALID);

            return false;
        }

        if ($this->containsHtmlEncodedEntities($value)) {
            $this->error(self::NOT_VALID);

            return false;
        }

        if (strlen($value) > self::URI_MAX_LENGTH) {
            $this->error(self::TOO_LONG);

            return false;
        }

        return true;
    }

    private function containsUnEncodedHtmlEntities(string $uri): bool
    {
        $test = str_replace('&', '', $uri);

        return htmlentities($test) !== $test;
    }

    private function containsHtmlEncodedEntities(string $uri): bool
    {
        return html_entity_decode($uri) !== $uri;
    }
}
