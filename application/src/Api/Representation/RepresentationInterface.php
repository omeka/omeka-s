<?php
namespace Omeka\Api\Representation;

use JsonSerializable;
use Laminas\EventManager\EventManagerAwareInterface;

/**
 * The representation interface
 *
 * A representation wraps around and provides a standard interface to data. It
 * has two primary functions:
 *
 *   - Serialize into a JSON-LD object
 *   - Pass around internally as a rich, read-only data object
 */
interface RepresentationInterface extends JsonSerializable, EventManagerAwareInterface
{
}
