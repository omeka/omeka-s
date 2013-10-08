<?php
namespace OmekaTest\Model;

use Omeka\Model\Exception\EntityValidationException;

class EntityValidationExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetsAndGetsErrorMap()
    {
        $errorMap = $this->getMock('Omeka\Error\Map');
        $exception = new EntityValidationException;
        $exception->setErrorMap($errorMap);
        $this->assertSame($errorMap, $exception->getErrorMap());
    }
}
