<?php defined('SYSPATH') OR die('No direct script access.');

/**
 * Asset management.
 *
 * Examples:
 * # Creates compressed assets
 * Assets --do=migrate
 * 
 * Options:
 *   --do      Function to be done.
 * 
 * Functions:
 *   migrate     Creates compressed assets
 * 
 * @author     Gian Carlo Val Ebao
 * @version    1.0
 */
class Assets_Task_Assets extends Minion_Task {
    /**
     * Parameters that are accepted by this task.
     */
    protected $_options = array(
        'do' => null,
    );
    
    /**
     * Validate parameters passed to the task.
     */
    public function build_validation(Validation $validation)
    {
        return parent::build_validation($validation)
            ->rule('do', 'not_empty')
            ->rule('do', 'in_array', array(':value', array('migrate')));
    }
    
    /**
     * To be executed by Minion
     *
     * @param  array  $params  Parameters received by Minion
     */
    protected function _execute(array $params)
    {
        $method = $params['do'];
        $this->$method($params);
    }
    
    protected static function rmdir($dir)
    {
        if (!is_dir($dir))
        {
            return false;
        }
        
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path)
        {
            $path->isFile() ? unlink($path->getPathname()) : rmdir($path->getPathname());
        }
        
        rmdir($dir);
    }
    
    protected static function gzip($file, $body)
    {
        // Open the gz file (w9 is the highest compression)
        $fp = gzopen($file, 'w9');
        
        // Compress the file
        gzwrite($fp, $body);
        
        // Close the gz file and we are done
        gzclose($fp);
    }
    
    protected static function _message($message)
    {
        ob_end_flush();
        echo $message ."\n";
        ob_start();
    }
}