<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when access to a resource is forbidden.
     * Maps to HTTP 403.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class ForbiddenException extends HttpException {

	/**
	 * @param string          $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $code = 403, $previous = null) {
	    parent::__construct($message, $code, $previous);
	}

    }

}
