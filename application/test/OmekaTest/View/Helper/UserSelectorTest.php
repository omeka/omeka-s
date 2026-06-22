<?php
namespace OmekaTest\View\Helper;

use Omeka\Test\TestCase;
use Omeka\View\Helper\UserSelector;

class UserSelectorTest extends TestCase
{
    public function testGroupUsersByInitialGroupsMultibyteAndAscii()
    {
        $helper = new class extends UserSelector {
            public function callGroupUsersByInitial(iterable $users): array
            {
                return $this->groupUsersByInitial($users);
            }
        };

        // Create simple user stubs with name() and email()
        $u1 = new class {
            public function name()
            {
                return 'alice';
            }
        };
        $u2 = new class {
            public function name()
            {
                return 'Álvaro';
            }
        };
        $u3 = new class {
            public function name()
            {
                return '山田';
            }
        };
        $u4 = new class {
            public function name()
            {
                return 'bob';
            }
        };

        $result = $helper->callGroupUsersByInitial([$u1, $u2, $u3, $u4]);

        // Keys should be uppercase initials, multibyte-safe
        $this->assertArrayHasKey('A', $result);
        $this->assertArrayHasKey('Á', $result);
        $this->assertArrayHasKey('山', $result);
        $this->assertArrayHasKey('B', $result);

        $this->assertSame('alice', $result['A'][0]->name());
        $this->assertSame('Álvaro', $result['Á'][0]->name());
        $this->assertSame('山田', $result['山'][0]->name());
        $this->assertSame('bob', $result['B'][0]->name());
    }
}
