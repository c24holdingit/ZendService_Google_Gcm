<?php
/**
 * Zend Framework (http://framework.zend.com/).
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 *
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 *
 * @category  ZendService
 */
namespace ZendService\Google\Exception;

/**
 * Runtime Exception.
 *
 * @category  ZendService
 */
class RuntimeException extends \RuntimeException
{
    protected $data = array();

    /**
     * Constructor
     * 
     * @param string $message
     * @param integer $code
     * @param \Exception $previous
     * @param array $data
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null, array $data = array())
    {
        parent::__construct($message, $code, $previous);
        
        $this->data = $data;
    }
    
    /**
     * Sets an information
     * 
     * @param mixed $key
     * @param mixed $value
     */
    public function setData($key, $value)
    {
        $this->data[$key] = $value;
    }
    
    /**
     * Returns an information stored for that key
     * 
     * @param mixed $key
     * @return mixed 
     */
    public function getData($key) 
    {
        if(!isset($this->data[$key])) {
            return null;
        }
        
        return $this->data[$key];
    }
}
