<?php
namespace Omeka\File\Thumbnailer;

class GraphicsMagick extends ImageMagick
{
    protected function getCommandArguments($strategy, $constraint, $options = []) {
        $args = parent::getCommandArguments($strategy, $constraint, $options);

        if (($key = array_search("-alpha remove", $args)) !== false) {
            unset($args[$key]);
        }
        return $args;
    }
}
