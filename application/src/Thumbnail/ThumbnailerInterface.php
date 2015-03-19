<?php
namespace Omeka\Thumbnail;

use Zend\ServiceManager\ServiceLocatorAwareInterface;

/**
 * @todo In create() and createSquare() copy $source to temp file:
 *   - convert to JPEG
 *   - resize according to $constraint
 *   - append .jpeg extension to file
 *   - return the temp thumbnail path
 */
interface ThumbnailerInterface extends ServiceLocatorAwareInterface 
{
    public function setSource($source);
    public function create($constraint);
    public function createSquare($constraint);
}
