<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when a requested resource (action, config, view, …) does not exist.
     * Maps to HTTP 404.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class NotFoundException extends HttpException {

	/**
	 * @param string          $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $code = 404, $previous = null) {
	    parent::__construct($message, $code, $previous);
	}

    }

}
