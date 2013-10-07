<?php
namespace OmekaTest\Model;

class AbstractEntityTest extends \PHPUnit_Framework_TestCase
{
    protected $abstractEntity;

    public function setUp()
    {
        $this->abstractEntity = $this->getMockForAbstractClass(
            'Omeka\Model\Entity\AbstractEntity'
        );
    }

    public function testGetsValidationException()
    {
        $this->assertInstanceOf(
            'Omeka\Model\Exception\EntityValidationException',
            $this->abstractEntity->getValidationException()
        );
    }

    public function testSetsValidationErrors()
    {
        $this->abstractEntity->setValidationError('foo', 'foo_message_one');
        $this->abstractEntity->setValidationError('foo', 'foo_message_two');
        $this->abstractEntity->setValidationError('bar', 'bar_message');
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
            $this->abstractEntity->getValidationException()->getValidationErrors()
        );
    }

    public function testHasValidationErrors()
    {
        $this->assertFalse($this->abstractEntity->hasValidationErrors());
        $this->abstractEntity->setValidationError('foo', 'foo_message');
        $this->assertTrue($this->abstractEntity->hasValidationErrors());
    }

    public function testClearsValidationErrors()
    {
        $this->abstractEntity->setValidationError('foo', 'foo_message');
        $this->abstractEntity->clearValidationErrors();
        $this->assertEquals(
            array(),
            $this->abstractEntity->getValidationException()->getValidationErrors()
        );
    }
}
