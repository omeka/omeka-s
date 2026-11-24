<?php
namespace OmekaTest\Stdlib;

use Laminas\Session\Container;
use Omeka\File\Stager;
use Omeka\Stdlib\WorkflowStates;
use Omeka\Test\DbTestCase;

/**
 * Workflows kept with WorkflowStates, with files and without, in a session
 * container of the tests' own.
 */
class WorkflowStatesTest extends DbTestCase
{
    const SESSION_NAME = 'OmekaTestWorkflowStates';

    /**
     * @var Stager
     */
    protected $stager;

    /**
     * @var WorkflowStates
     */
    protected $workflowStates;

    /**
     * The tokens of the workflows a test starts, ended after it, so their
     * files don't stay.
     *
     * @var string[]
     */
    protected $tokens = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->stager = self::getApplication()->getServiceManager()->get('Omeka\File\Stager');
        $this->workflowStates = new WorkflowStates(self::SESSION_NAME);
    }

    public function tearDown(): void
    {
        foreach ($this->tokens as $token) {
            $this->workflowStates->delete($token);
        }
        parent::tearDown();
    }

    /**
     * Stage a copy of one of RdfImporterTest's files.
     *
     * @return string The staged file's path
     */
    protected function stage()
    {
        return $this->stager->stage(__DIR__ . '/_files/compare.ttl');
    }

    /**
     * Start a workflow with a staged file under "source".
     *
     * @return string The workflow's token
     */
    protected function createWithFile(array $state = [])
    {
        return $this->tokens[] = $this->workflowStates->create($state, ['source' => $this->stage()]);
    }

    public function testGetGivesTheTokenAndTheState()
    {
        $token = $this->workflowStates->create(['step' => 'review']);
        $this->assertSame(['token' => $token, 'files' => [], 'step' => 'review'], $this->workflowStates->get($token));
        $this->workflowStates->delete($token);
        $this->assertNull($this->workflowStates->get($token));
    }

    public function testGetGivesTheFilesByName()
    {
        $token = $this->createWithFile(['step' => 'review']);
        $workflow = $this->workflowStates->get($token);
        $this->assertSame(['source'], array_keys($workflow['files']));
        $this->assertFileEquals(__DIR__ . '/_files/compare.ttl', $workflow['files']['source']);
        $this->assertSame('review', $workflow['step']);
    }

    public function testDeleteDeletesTheFiles()
    {
        $token = $this->createWithFile();
        $this->workflowStates->setFile($token, 'notes', $this->stage());
        $files = $this->workflowStates->get($token)['files'];
        $this->workflowStates->delete($token);
        $this->assertNull($this->workflowStates->get($token));
        $this->assertFileDoesNotExist($files['source']);
        $this->assertFileDoesNotExist($files['notes']);
    }

    public function testAWorkflowWithAFileGoneHasEnded()
    {
        $token = $this->createWithFile();
        $this->workflowStates->setFile($token, 'notes', $this->stage());
        unlink($this->workflowStates->get($token)['files']['notes']);
        $this->assertNull($this->workflowStates->get($token));
    }

    public function testDeleteIsSafeOnceEnded()
    {
        $token = $this->createWithFile();
        $this->workflowStates->delete($token);
        $this->workflowStates->delete($token);
        $this->workflowStates->delete('unknown');
        $this->assertNull($this->workflowStates->get($token));
    }

    public function testAWorkflowEndsAfterADay()
    {
        $token = $this->createWithFile();
        $file = $this->workflowStates->get($token)['files']['source'];

        // Started more than a day ago, as only the session container can
        // say.
        $session = new Container(self::SESSION_NAME);
        $stored = $session->offsetGet($token);
        $stored['started'] = time() - WorkflowStates::MAX_AGE - 60;
        $session->offsetSet($token, $stored);
        $this->assertNull($this->workflowStates->get($token));

        // The next workflow of its kind forgets it and deletes its file.
        $this->tokens[] = $this->workflowStates->create([]);
        $this->assertFalse($session->offsetExists($token));
        $this->assertFileDoesNotExist($file);
    }

    public function testEachPageAddsItsEntriesForThePagesAfterIt()
    {
        // Start, then pages A and B, then the finish.
        $token = $this->createWithFile(['format' => 'turtle']);
        $files = $this->workflowStates->get($token)['files'];
        $this->workflowStates->update($token, ['page_a' => ['label' => 'Letter']]);
        // Back to page A and Next again: its entries replace the first ones.
        $this->workflowStates->update($token, ['page_a' => ['label' => 'Letters']]);
        $this->workflowStates->update($token, ['page_b' => ['lang' => 'fr']]);

        $this->assertSame([
            'token' => $token,
            'files' => $files,
            'format' => 'turtle',
            'page_a' => ['label' => 'Letters'],
            'page_b' => ['lang' => 'fr'],
        ], $this->workflowStates->get($token));
        $this->workflowStates->delete($token);
        $this->assertFileDoesNotExist($files['source']);
    }

    public function testAModuleEntersAtTheThirdStep()
    {
        // Started with what page A would have added, then on to page B.
        $token = $this->createWithFile(['format' => 'turtle', 'page_a' => ['label' => 'Letters']]);
        $this->workflowStates->update($token, ['page_b' => ['lang' => 'fr']]);

        // The finish reads it as it would a workflow that went through page A.
        $workflow = $this->workflowStates->get($token);
        $this->assertSame(['label' => 'Letters'], $workflow['page_a']);
        $this->assertSame(['lang' => 'fr'], $workflow['page_b']);
        $this->assertSame('turtle', $workflow['format']);
    }

    public function testWhatGetGivesCanBeChangedAndPassedBack()
    {
        $token = $this->createWithFile(['format' => 'turtle']);
        $workflow = $this->workflowStates->get($token);
        $workflow['page_a'] = ['label' => 'Letters'];
        $this->workflowStates->update($token, $workflow);

        $this->assertSame([
            'token' => $token,
            'files' => $workflow['files'],
            'format' => 'turtle',
            'page_a' => ['label' => 'Letters'],
        ], $this->workflowStates->get($token));
        // The token and files aren't kept in the state as well.
        $session = new Container(self::SESSION_NAME);
        $this->assertSame(['format', 'page_a'], array_keys($session->offsetGet($token)['state']));
    }

    public function testUpdateKeepsWhenTheWorkflowStarted()
    {
        $token = $this->createWithFile();
        // Started almost a day ago, as only the session container can say.
        $session = new Container(self::SESSION_NAME);
        $stored = $session->offsetGet($token);
        $started = time() - WorkflowStates::MAX_AGE + 60;
        $stored['started'] = $started;
        $session->offsetSet($token, $stored);

        $this->workflowStates->update($token, ['page_a' => []]);
        $this->assertSame($started, $session->offsetGet($token)['started']);
    }

    public function testUpdateDoesNothingOnceEnded()
    {
        $token = $this->createWithFile();
        $this->workflowStates->delete($token);
        $this->workflowStates->update($token, ['page_a' => []]);
        $this->assertNull($this->workflowStates->get($token));
        $session = new Container(self::SESSION_NAME);
        $this->assertFalse($session->offsetExists($token));
    }

    public function testAPageKeepsAFileAddedPartway()
    {
        // A workflow without a file at its start, whose page B takes one.
        $token = $this->tokens[] = $this->workflowStates->create([]);
        $notes = $this->stage();
        $this->workflowStates->setFile($token, 'notes', $notes);
        $this->assertSame(['notes' => $notes], $this->workflowStates->get($token)['files']);

        // Back to page B, and another file chosen: the first is deleted.
        $other = $this->stage();
        $this->workflowStates->setFile($token, 'notes', $other);
        $this->assertSame(['notes' => $other], $this->workflowStates->get($token)['files']);
        $this->assertFileDoesNotExist($notes);

        // The same file kept again stays.
        $this->workflowStates->setFile($token, 'notes', $other);
        $this->assertFileExists($other);
    }

    public function testSetFileOnceEndedDeletesTheFile()
    {
        $token = $this->createWithFile();
        $this->workflowStates->delete($token);
        $notes = $this->stage();
        $this->workflowStates->setFile($token, 'notes', $notes);
        $this->assertFileDoesNotExist($notes);
        $this->assertNull($this->workflowStates->get($token));
    }

    public function testRemoveFileDeletesItAndForgetsItsName()
    {
        // A page that took two attachments, sent again with one.
        $token = $this->createWithFile();
        $this->workflowStates->setFile($token, 'attachment-0', $this->stage());
        $this->workflowStates->setFile($token, 'attachment-1', $this->stage());
        $second = $this->workflowStates->get($token)['files']['attachment-1'];
        $this->workflowStates->removeFile($token, 'attachment-1');

        $this->assertSame(['source', 'attachment-0'], array_keys($this->workflowStates->get($token)['files']));
        $this->assertFileDoesNotExist($second);
        // A name it doesn't keep is no matter.
        $this->workflowStates->removeFile($token, 'attachment-1');
        $this->assertNotNull($this->workflowStates->get($token));
    }

    public function testKindsDontShareTokens()
    {
        $token = $this->createWithFile();
        $other = new WorkflowStates(self::SESSION_NAME . 'Other');
        $this->assertNull($other->get($token));
        $other->delete($token);
        $this->assertNotNull($this->workflowStates->get($token));
    }
}
