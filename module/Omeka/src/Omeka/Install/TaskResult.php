<?php
namespace Omeka\Install;

/**
 * Results and messages for a task in the installation process
 * @author patrickmj
 *
 */

class TaskResult
{
    protected $success;
    protected $task;
    protected $messages = array();
    
    const TASK_RESULT_INFO = 'INFO';
    const TASK_RESULT_ERROR = 'ERROR';
    const TASK_RESULT_WARNING = 'WARNING';
    
    public function __construct(TaskInterface $task)
    {
        $this->task = $task;    
    }
    
    /**
     * Adds a message based on an Exception thrown
     * @param \Exception $exception
     * @param string $message A human-friendly message to include for guidance
     */
    
    public function addExceptionMessage(\Exception $exception, $message)
    {
        $this->messages[] = array(
            'message'=>$message,
            'code'=>$exception->getCode(),
            'exception'=>$exception);        
    }
    
    /**
     * A message to add based on the tasks success or failure conditions
     * @param string $message
     * @param mixed $code
     */
    public function addMessage($message, $code = null)
    {
        if(!$code) {
            $code = self::TASK_RESULT_INFO;
        }
        
        $this->messages[] = array(
            'message'=>$message,
            'code'=>$code,
            'exception'=>false);
    }
    
    public function getMessages()
    {
        return array('taskName' => $this->task->getTaskName(), 'messages' => $this->messages);
    }
    
    public function getSuccess()
    {
        return $this->success;
    }
    
    public function setSuccess($success)
    {
        $this->success = $success;
    }
}