<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when authentication is required but missing or invalid.
     * Maps to HTTP 401.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class UnauthorizedException extends HttpException {

	/**
	 * @param string          $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $code = 401, $previous = null) {
	    parent::__construct($message, $code, $previous);
	}

    }

}
