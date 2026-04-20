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
	 * @param array $parms <p>The key "exception" is mandatory and must be of type Exception.</p>
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct($parms = []) {
	   parent::__construct($parms) ;
	    if(!key_exists('exception', $this->_parms) || ($this->_parms['exception'] instanceof \Exception) === false) {
		throw new Exception\InvalidArgumentException('Invalid argument $parms["exception"] is not instance of Exception on new ' .
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