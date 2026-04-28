<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when a rendering method, getter, or feature is not implemented.
     * Maps to HTTP 501.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class NotImplementedException extends HttpException {

	/**
	 * @param string          $message
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $code = 501, $previous = null) {
	    parent::__construct($message, $code, $previous);
	}

    }

}
