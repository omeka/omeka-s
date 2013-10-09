<?php
namespace OmekaTest\Model;

use Omeka\Model\Exception\EntityValidationException;

class EntityValidationExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetsAndGetsErrorStore()
    {
        $errorStore = $this->getMock('Omeka\StdLib\ErrorStore');
        $exception = new EntityValidationException;
        $exception->setErrorStore($errorStore);
        $this->assertSame($errorStore, $exception->getErrorStore());
    }
}
