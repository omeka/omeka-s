<?php
namespace Omeka\Form\Element;

use Laminas\Form\Element\Text;

/**
 * A text input that accepts any <length> CSS data type.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/length
 */
class LengthCssDataType extends Text
{
    // A number followed by a unit.
    const PATTERN = '^(\d*\.?\d+)(%|cap|ch|em|ex|ic|lh|rem|rlh|vh|svh|lvh|dvh|vw|svw|lvw|dvw|vmax|svmax|lvmax|dvmax|vmin|svmin|lvmin|dvmin|vb|svb|lvb|dvb|vi|svi|lvi|dvi|cqw|cqh|cqi|cqb|cqmin|cqmax|px|cm|mm|Q|in|pc|pt)?$';

    public function __construct($name = null, iterable $options = [])
    {
        parent::__construct($name, $options);
        $this->setAttribute('pattern', self::PATTERN);
    }
}
