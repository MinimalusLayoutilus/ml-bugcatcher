<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown by actionDefault() when no matching action method exists on a controller.
     * Programm::runn() catches this to trigger a 404 response.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class ActionNotFoundException extends NotFoundException {

	public function __construct($action, $class, $code = 404, $previous = null) {
	    parent::__construct(
		'Action "' . $action . '" not found in ' . $class,
		$code,
		$previous
	    );
	}

    }

}
