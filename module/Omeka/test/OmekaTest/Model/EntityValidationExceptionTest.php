<?php
namespace OmekaTest\Model;

use Omeka\Model\Exception\EntityValidationException;

class EntityValidationExceptionTest extends \PHPUnit_Framework_TestCase
{
    protected $entityValidationException;

    public function setUp()
    {
        $this->entityValidationException = new EntityValidationException;
    }

    public function testSetsAndGetsValidationErrors()
    {
        $this->entityValidationException->addValidationError('foo', 'foo_message_one');
        $this->entityValidationException->addValidationError('foo', 'foo_message_two');
        $this->entityValidationException->addValidationError('bar', 'bar_message');
        $this->assertEquals(
            array(
                'foo' => array(
                    'foo_message_one',
                    'foo_message_two',
                ),
                'bar' => array(
                    'bar_message'
                )
            ),
            $this->entityValidationException->getValidationErrors()
        );
    }

    public function testClearsValidationErrors()
    {
        $this->entityValidationException->addValidationError('foo', 'foo_message');
        $this->entityValidationException->clearValidationErrors();
        $this->assertEquals(
            array(),
            $this->entityValidationException->getValidationErrors()
        );
    }
}
