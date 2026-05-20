<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown to trigger an HTTP redirect.
     * Maps to HTTP 301 (permanent) or 302 (temporary).
     * Programm::runn() catches this to issue the Location header.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class RedirectException extends HttpException {

	/** @var string Target URL */
	protected $_location;

	/**
	 * @param string          $location   Target URL
	 * @param int             $statusCode 301 or 302
	 * @param \Exception|null $previous
	 */
	public function __construct($location, $statusCode = 301, $previous = null) {
	    $this->_location = (string) $location;
	    parent::__construct('Redirect to ' . $this->_location, $statusCode, $previous);
	}

	/**
	 * @return string
	 */
	public function getLocation() {
	    return $this->_location;
	}

    }

}
