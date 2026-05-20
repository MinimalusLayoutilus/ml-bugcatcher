<?php

namespace mnhcc\ml\classes\EventParms {

    use \mnhcc\ml\classes\Exception,
	\mnhcc\ml\interfaces,
	\mnhcc\ml\classes,
	\mnhcc\ml\classes\EventParms;
    /**
     * Description of ExceptionEventParms
     *
     * @author carschrotter
     */
    class ExceptionEventParms extends EventParms {
	
	/**
	 * @param array $parms <p>The key "exception" is mandatory and must
	 *                     implement Throwable (PHP 7+) or extend
	 *                     Exception (PHP 5.6).  PHP 8.x runtime errors
	 *                     (TypeError, ValueError, …) descend from
	 *                     \Error → \Throwable but NOT from \Exception,
	 *                     so the historical Exception-only check
	 *                     fataled when the shutdown handler caught a
	 *                     runtime fatal on PHP 8.</p>
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct($parms = []) {
	   parent::__construct($parms) ;
	    if (!key_exists('exception', $this->_parms)) {
		throw new Exception\InvalidArgumentException('Invalid argument $parms["exception"] is missing on new ' .
			static::getCalledClass() . '($parms)');
	    }
	    $ex = $this->_parms['exception'];
	    $ok = ($ex instanceof \Exception);
	    if (!$ok && \interface_exists('Throwable')) {
		// PHP 7+ — accept any Throwable (covers \Error subclasses).
		$ok = ($ex instanceof \Throwable);
	    }
	    if (!$ok) {
		throw new Exception\InvalidArgumentException('Invalid argument $parms["exception"] is not instance of Exception/Throwable on new ' .
			static::getCalledClass() . '($parms)');
	    }
	}
	
	/**
	 * return the Exception
	 * @return \Exception
	 */
	public function getException() {
	    return $this->_parms['exception'];
	}
    }

}