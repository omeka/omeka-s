<?php
namespace OmekaTest\Db\Migration;

use Omeka\Db\Migration\Manager as MigrationManager;
use Omeka\Test\TestCase;

class ManagerTest extends TestCase
{
    public $migrations = array(
        '1' => array(
            'path' => '/path/1',
            'class' => 'Migration1'
        ),
        '2' => array(
            'path' => '/path/2',
            'class' => 'Migration2'
        )
    );

    public function testGetMigrationsWithNoneCompleted()
    {
        $manager = $this->getMock('Omeka\Db\Migration\Manager',
            array('getCompletedMigrations', 'getAvailableMigrations'),
            array(), '', false);
        $manager->expects($this->once())
            ->method('getCompletedMigrations')
            ->will($this->returnValue(array()));
        $manager->expects($this->once())
            ->method('getAvailableMigrations')
            ->will($this->returnValue($this->migrations));

        $this->assertEquals($this->migrations, $manager->getMigrationsToPerform());
    }

    public function testGetMigrationsWithAllCompleted()
    {
        $manager = $this->getMock('Omeka\Db\Migration\Manager',
            array('getCompletedMigrations', 'getAvailableMigrations'),
            array(), '', false);
        $manager->expects($this->once())
            ->method('getCompletedMigrations')
            ->will($this->returnValue(array_keys($this->migrations)));
        $manager->expects($this->once())
            ->method('getAvailableMigrations')
            ->will($this->returnValue($this->migrations));

        $this->assertEquals(array(), $manager->getMigrationsToPerform());
    }

    public function testLoadMigrationWithProperClass()
    {
        $path = __DIR__ . '/_files/1_MockMigration.php';
        $class = 'OmekaTest\Db\Migration\MockMigration';

        $sl = $this->getMockForAbstractClass('Zend\ServiceManager\ServiceLocatorInterface');
        $manager = $this->getMock('Omeka\Db\Migration\Manager',
            array('getServiceLocator'), array(), '', false);
        $manager->expects($this->once())
            ->method('getServiceLocator')
            ->will($this->returnValue($sl));

        $migration = $manager->loadMigration($path, $class);
        $this->assertInstanceOf($class, $migration);
        $this->assertEquals($sl, $migration->getServiceLocator());
    }

    /**
     * @expectedException Omeka\Db\Migration\Exception\ClassNotFoundException
     */
    public function testLoadMigrationWithBadClassName()
    {
        $path = __DIR__ . '/_files/1_MockMigration.php';
        $class = 'OmekaTest\Db\Migration\BogusMigration';

        $translator = $this->getMockForAbstractClass('Zend\I18n\Translator\TranslatorInterface');
        $translator->expects($this->once())
            ->method('translate')
            ->will($this->returnArgument(0));

        $manager = $this->getMock('Omeka\Db\Migration\Manager',
            array('getTranslator'), array(), '', false);
        $manager->expects($this->once())
            ->method('getTranslator')
            ->will($this->returnValue($translator));

        $manager->loadMigration($path, $class);
    }

    /**
     * @expectedException Omeka\Db\Migration\Exception\ClassNotFoundException
     */
    public function testLoadMigrationWithInvalidMigration()
    {
        $path = __DIR__ . '/_files/2_MockInvalidMigration.php';
        $class = 'OmekaTest\Db\Migration\MockInvalidMigration';

        $translator = $this->getMockForAbstractClass('Zend\I18n\Translator\TranslatorInterface');
        $translator->expects($this->once())
            ->method('translate')
            ->will($this->returnArgument(0));

        $manager = $this->getMock('Omeka\Db\Migration\Manager',
            array('getTranslator'), array(), '', false);
        $manager->expects($this->once())
            ->method('getTranslator')
            ->will($this->returnValue($translator));

        $manager->loadMigration($path, $class);
    }

    public function testRecordMigration()
    {
        $version = '1';
        $tableName = 'migration';

        $connection = $this->getMock('Doctrine\DBAL\Connection',
            array(), array(), '', false);
        $connection->expects($this->once())
            ->method('insert')
            ->with(
                $this->equalTo($tableName),
                $this->equalTo(array('version' => $version))
            );

        $sm = $this->getServiceManager(array(
            'Omeka\Connection' => $connection
        ));

        $manager = new MigrationManager(array('entity' => 'Entity'));
        $manager->setServiceLocator($sm);
        $manager->recordMigration($version);
    }

    public function testGetAvailableMigrations()
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . '_files';
        $namespace = 'BogusNamespace';

        $migrations = array(
            '1' => array(
                'path' => $path . DIRECTORY_SEPARATOR . '1_MockMigration.php',
                'class' => $namespace . '\MockMigration'
            ),
            '2' => array(
                'path' => $path . DIRECTORY_SEPARATOR . '2_MockInvalidMigration.php',
                'class' => $namespace . '\MockInvalidMigration'
            )
        );

        $manager = new MigrationManager(array('path' => $path, 'namespace' => $namespace));
        $this->assertEquals($migrations, $manager->getAvailableMigrations());
    }

    /**
     * @todo the extremely new matcher method ->withConsecutive() would be
     * very useful here
     */
    public function testUpgrade()
    {
        $migration = $this->getMockForAbstractClass('Omeka\Db\Migration\MigrationInterface');
        $migration->expects($this->exactly(2))
            ->method('up');

        $manager = $this->getMock('Omeka\Db\Migration\Manager',
            array('getMigrationsToPerform', 'loadMigration', 'recordMigration'),
            array(), '', false);
        $manager->expects($this->once())
            ->method('getMigrationsToPerform')
            ->will($this->returnValue($this->migrations));
        $manager->expects($this->exactly(2))
            ->method('loadMigration')
            ->will($this->returnValue($migration));
        $manager->expects($this->exactly(2))
            ->method('recordMigration');

        $manager->upgrade();
    }
}
