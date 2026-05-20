<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when no Control class can be resolved for the current request and
     * the configured fallback controller could not load a config either.
     *
     * Typical chaining: a fallback controller (e.g. ControlFilePage) raises
     * ConfigNotFoundException because no content config matches the URL; the
     * caller then attempts to locate a specific Control{Segment} class for the
     * URL's first segment, fails, and wraps the original ConfigNotFoundException
     * as $previous on this exception so the trace preserves both the missing
     * config key and the missing controller name.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class ControllerNotFoundException extends NotFoundException {

	/**
	 * Class name (or segment) the framework looked for.
	 * @var string
	 */
	protected $_controllerName;

	/**
	 * @param string          $controllerName  Fully-qualified class name or
	 *                                         the bare URL segment that was
	 *                                         translated into a class name.
	 * @param string|null     $context         Where the lookup happened (class, path).
	 * @param int             $code
	 * @param \Exception|null $previous        Typically the ConfigNotFoundException
	 *                                         that triggered the controller search.
	 */
	public function __construct($controllerName, $context = null, $code = 404, $previous = null) {
	    $this->_controllerName = (string) $controllerName;
	    $message = 'Controller "' . $controllerName . '" not found';
	    if ($context !== null) {
		$message .= ' in ' . $context;
	    }
	    parent::__construct($message, $code, $previous);
	}

	/**
	 * @return string
	 */
	public function getControllerName() {
	    return $this->_controllerName;
	}

    }

}
