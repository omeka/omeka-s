<?php
namespace Omeka\Stdlib;

/**
 * Standard class checking functions.
 */
class ClassCheck
{
    /**
     * Check whether the class implements the interface.
     *
     * Most helpful when checking against a class name (i.e. not an object,
     * which can be done with instanceof). Use as a stopgap until Omeka's PHP
     * requirement meets 5.3.7, when is_subclass_of() works with interfaces.
     *
     * @see http://us1.php.net/is_subclass_of
     * @param string $interface The interface name
     * @param string|object $class The class name or object
     * @return bool
     */
    static public function isInterfaceOf($interface, $class)
    {
        return in_array($interface, class_implements($class));
    }
}
