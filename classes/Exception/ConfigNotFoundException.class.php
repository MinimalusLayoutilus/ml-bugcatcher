<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when a required config key or config file cannot be found.
     * Programm::runn() catches this to trigger a 404 response.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class ConfigNotFoundException extends NotFoundException {

	/**
	 * @param string          $key      The config key or file that was not found.
	 * @param string|null     $context  Where the lookup was attempted (class name, path, …).
	 * @param int             $code
	 * @param \Exception|null $previous
	 */
	public function __construct($key, $context = null, $code = 404, $previous = null) {
	    $message = 'Config "' . $key . '" not found';
	    if ($context !== null) {
		$message .= ' in ' . $context;
	    }
	    parent::__construct($message, $code, $previous);
	}

    }

}
