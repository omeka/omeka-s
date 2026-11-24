<?php
namespace Omeka\Stdlib;

use Laminas\Session\Container;

/**
 * The states of workflows that run across several pages, such as a vocabulary
 * import. Each is kept in the session under a token its pages carry, until it
 * ends or a day has passed. A workflow starts with what it's given, and each
 * page may add what the admin entered there, once checked, for the pages after
 * it. A workflow's staged files (Omeka\File\Stager), each kept under a name of
 * its own, are deleted when it ends.
 *
 * The service that carries a workflow, which its pages and modules call:
 *
 * - Holds every check that matters, since a module calls it without a form. A
 *   page's form may also check shape, such as required fields, for the admin.
 * - May start a workflow partway, for a module, with what the pages before
 *   would have added, each checked as its page checks it.
 * - Keeps a page's checked entries with update(), and any file the admin
 *   chose there with setFile(), when the page's Next succeeds. A page opened
 *   before the pages ahead of it are done sends the admin to the first that
 *   isn't, and a page's form starts from what was kept for it.
 * - Checks every page's kept entries again at the finish, since Omeka's data
 *   may have changed, and sends the admin to a page that now fails. So each
 *   page's check accepts what it returned.
 * - Checks the user's permission itself, at the start and at the finish,
 *   where the finish writes without going through the API.
 * - Makes the finish safe to run twice, since overlapping requests, from a
 *   double-click or two tabs, can both reach it: by a natural key the
 *   database refuses a second time, or by keeping the token with what the
 *   finish makes.
 */
class WorkflowStates
{
    /**
     * Seconds a workflow lasts, as long as a staged file.
     */
    const MAX_AGE = 86400;

    /**
     * @var string
     */
    protected $sessionName;

    /**
     * @var Container|null
     */
    protected $session;

    /**
     * @param string $sessionName The session container's name, one for each
     *   kind of workflow: in core, "Omeka" and the service's name, as
     *   OmekaVocabularyImport; in a module, the module's name first
     */
    public function __construct($sessionName)
    {
        $this->sessionName = $sessionName;
    }

    /**
     * Start keeping a workflow's state, first ending any of its kind started
     * more than a day ago.
     *
     * @param array $state What the workflow's pages need. "token" and
     *   "files", which get() adds, are left out.
     * @param array $files The paths of the workflow's staged files, if it has
     *   any, each under a name of its own, as "source"
     * @return string The workflow's token
     */
    public function create(array $state, array $files = [])
    {
        unset($state['token'], $state['files']);
        $this->deleteOld();
        $token = bin2hex(random_bytes(16));
        $this->getSession()->offsetSet($token, [
            'state' => $state,
            'files' => $files,
            'started' => time(),
        ]);
        return $token;
    }

    /**
     * Get a workflow's state.
     *
     * @param string $token
     * @return array|null token, files (its staged files' paths, by name), and
     *   the state; null once it has ended, a day has passed, or any of its
     *   files is gone
     */
    public function get($token)
    {
        $stored = $this->getSession()->offsetGet((string) $token);
        if (!is_array($stored) || $stored['started'] < time() - self::MAX_AGE) {
            return null;
        }
        foreach ($stored['files'] as $file) {
            if (!is_readable($file)) {
                return null;
            }
        }
        return ['token' => (string) $token, 'files' => $stored['files']] + $stored['state'];
    }

    /**
     * Add to a workflow's state, as a page does with what the admin entered
     * there, once checked. It keeps its files, and still ends a day after it
     * started. Does nothing once it has ended.
     *
     * @param string $token
     * @param array $state What to add, each key replacing any the state has.
     *   "token" and "files", which get() adds, are left out, so what get()
     *   gives can be changed and passed back.
     */
    public function update($token, array $state)
    {
        if (null === $this->get($token)) {
            return;
        }
        unset($state['token'], $state['files']);
        $session = $this->getSession();
        $stored = $session->offsetGet((string) $token);
        $stored['state'] = array_replace($stored['state'], $state);
        $session->offsetSet((string) $token, $stored);
    }

    /**
     * Keep a staged file under a name, as a page does with a file the admin
     * chose there, once checked. A file kept under that name before is
     * deleted. Once the workflow has ended, the file is deleted instead, as
     * the workflow's files were.
     *
     * @param string $token
     * @param string $name
     * @param string $file The staged file's path
     */
    public function setFile($token, $name, $file)
    {
        if (null === $this->get($token)) {
            $this->deleteFile($file);
            return;
        }
        $session = $this->getSession();
        $stored = $session->offsetGet((string) $token);
        if (isset($stored['files'][$name]) && $stored['files'][$name] !== $file) {
            $this->deleteFile($stored['files'][$name]);
        }
        $stored['files'][$name] = $file;
        $session->offsetSet((string) $token, $stored);
    }

    /**
     * Delete the file a workflow keeps under a name, as a page does with a
     * file the admin no longer chooses there. Does nothing once the workflow
     * has ended, or when it keeps no file under that name.
     *
     * @param string $token
     * @param string $name
     */
    public function removeFile($token, $name)
    {
        if (null === $this->get($token)) {
            return;
        }
        $session = $this->getSession();
        $stored = $session->offsetGet((string) $token);
        if (!isset($stored['files'][$name])) {
            return;
        }
        $this->deleteFile($stored['files'][$name]);
        unset($stored['files'][$name]);
        $session->offsetSet((string) $token, $stored);
    }

    /**
     * End a workflow: forget its state and delete its files. Safe to call
     * once it has ended.
     *
     * @param string $token
     */
    public function delete($token)
    {
        $session = $this->getSession();
        $stored = $session->offsetGet((string) $token);
        if (is_array($stored)) {
            foreach ($stored['files'] as $file) {
                $this->deleteFile($file);
            }
        }
        $session->offsetUnset((string) $token);
    }

    /**
     * End the workflows of this kind started more than a day ago.
     */
    protected function deleteOld()
    {
        foreach ($this->getSession()->getArrayCopy() as $token => $stored) {
            if ($stored['started'] < time() - self::MAX_AGE) {
                $this->delete($token);
            }
        }
    }

    /**
     * Delete a staged file, if it's still there.
     *
     * @param string $file
     */
    protected function deleteFile($file)
    {
        if (is_file($file)) {
            unlink($file);
        }
    }

    /**
     * Get the session container, starting it only when needed.
     *
     * @return Container
     */
    protected function getSession()
    {
        if (!$this->session) {
            $this->session = new Container($this->sessionName);
        }
        return $this->session;
    }
}
