<?php

namespace mnhcc\ml\classes\exception {

    use \mnhcc\ml,
    \mnhcc\ml\classes,
    \mnhcc\ml\classes\Exception;

    /**
     * Description of ViewNotFoundException
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */
    class ViewNotFoundException extends RenderException {

	public function __construct($class, \ReflectionException $previous) {
	    $message = 'View: ' . $class . ' not Found';
	    Exception::__construct($message, $previous->getCode(), $previous);
	    $this->_className = $class;
	}

    }

}