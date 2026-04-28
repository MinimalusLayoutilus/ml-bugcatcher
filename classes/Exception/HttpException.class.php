<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Base class for all HTTP-related exceptions.
     * Carries an HTTP status code so Programm::runn() can dispatch
     * to Error::raise() or header() without inspecting the type.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class HttpException extends \mnhcc\ml\classes\Exception {

	/** @var int HTTP status code */
	protected $_statusCode;

	/**
	 * @param string          $message
	 * @param int             $statusCode  HTTP status code (e.g. 404, 301, 501)
	 * @param \Exception|null $previous
	 */
	public function __construct($message, $statusCode, $previous = null) {
	    $this->_statusCode = (int) $statusCode;
	    parent::__construct($message, $this->_statusCode, $previous);
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
	    return $this->_statusCode;
	}

    }

}
