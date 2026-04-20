<?php

namespace mnhcc\ml\classes\Exception {

    use \mnhcc\ml,
	\mnhcc\ml\classes,
	\mnhcc\ml\traits,
	\mnhcc\ml\interfaces,
	\mnhcc\ml\classes\Exception;

    /**
     * Description of RenderException
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    class RenderException extends Exception {

	protected $_className = false;
	protected $_methodName = false;

	/**
	 * 
	 * @param string $method the name of the called method
	 * @param string $class the name of the class
	 * @param int $code
	 * @param \Exception $previous
	 */
	public function __construct($method, $class, $code = 0, $previous = null) {
	    $this->_methodName = $method;
	    $this->_className = $class;
	    $message = 'Call to not callable method ' . $this->getClassName() . '::' . $this->getMethodName() . '()';
	    parent::__construct($message, $code, $previous);
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