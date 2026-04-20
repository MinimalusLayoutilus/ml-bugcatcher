<?php

namespace mnhcc\ml\classes\exception {

    use \mnhcc\ml\interfaces,
	\mnhcc\ml\traits;

    /**
     * Description of ReflectionMethodException
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 */
    class ReflectionMethodException extends \ReflectionException implements interfaces\Exception {

	use traits\Exception;

	protected $_className = false;
	protected $_methodName = false;

	/**
	 * 
	 * @param string $method the name of the called method
	 * @param string $class the name of the class
	 * @param int $code
	 * @param \Exception $previous
	 */
	public function __construct($method, $class, $code, $previous) {
	    $message = 'no Method ' . $method . '() implement in ' . $class;
	    parent::__construct($message, $code, $previous);
	    $this->_methodName = $method;
	    $this->_className = $class;
	}

	public function __toString() {
	    return $this->getMessage();
	}

	public function getClassName() {
	    return $this->_className;
	}

	public function getMethodName() {
	    return $this->_methodName;
	}

    }

}