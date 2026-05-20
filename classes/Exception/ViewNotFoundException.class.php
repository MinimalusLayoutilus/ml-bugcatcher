<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when the View class for a given controller cannot be found.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class ViewNotFoundException extends NotFoundException {

	/** @var string */
	protected $_className;

	public function __construct($class, \ReflectionException $previous) {
	    $this->_className = $class;
	    parent::__construct('View: ' . $class . ' not Found', 404, $previous);
	}

	public function getClassName() {
	    return $this->_className;
	}

    }

}
