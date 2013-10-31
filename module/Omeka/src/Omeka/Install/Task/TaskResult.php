<?php
namespace Omeka\Install\Task;

/**
 * Results and messages for a task in the installation process
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
    
    /**
     * Construct the TaskResult object, injecting a TaskInterface
     * 
     * @param TaskInterface $task
     */
    public function __construct(TaskInterface $task)
    {
        $this->task = $task;    
    }
    
    /**
     * Adds a message based on an Exception thrown
     * 
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
     * 
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
    
    /**
     * Return an array of messages about the result of the task
     * 
     * @return array
     */
    public function getMessages()
    {
        return array('taskName' => $this->task->getTaskName(), 'messages' => $this->messages);
    }
    
    /**
     * Return whether the task resulted in success
     * 
     * @return array
     */
    public function getSuccess()
    {
        return $this->success;
    }
    
    /**
     * Set the success status
     * 
     * @param boolean $success
     */
    public function setSuccess($success)
    {
        $this->success = $success;
    }
}