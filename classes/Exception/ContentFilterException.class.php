<?php

namespace mnhcc\ml\classes\Exception {

    /**
     * Thrown when a single ContentFilterReplacer filter (a CALLABLE / EVAL
     * listener registered against a `content.filter.{key}` hook) fails for
     * one matched token.
     *
     * Recoverable: the replacer catches it, hands it to `Error::report()`
     * (so it lands in the framework log and — when DEBUG is on — in the
     * BugCatcher overlay), and leaves the original token in place.  The
     * surrounding render keeps going.
     *
     * Carries the filter key and the offending token verbatim so the
     * overlay can show "filter `counter` failed for `{%counter%}`" without
     * the listener author having to re-format that.
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     */
    class ContentFilterException extends \mnhcc\ml\classes\Exception {

	/** @var string */
	protected $_filterKey;

	/** @var string */
	protected $_token;

	/**
	 * @param string          $filterKey  Filter hook key (e.g. "counter").
	 * @param string          $token      The matched token text (e.g. "{%counter%}").
	 * @param string          $reason     Short human-readable cause.
	 * @param \Exception|null $previous   Original failure to chain.
	 */
	public function __construct($filterKey, $token, $reason, $previous = null) {
	    $this->_filterKey = (string) $filterKey;
	    $this->_token     = (string) $token;
	    $message = 'Content filter "' . $this->_filterKey . '" failed for token '
		. $this->_token . ': ' . $reason;
	    parent::__construct($message, 0, $previous);
	}

	/**
	 * @return string
	 */
	public function getFilterKey() {
	    return $this->_filterKey;
	}

	/**
	 * @return string
	 */
	public function getToken() {
	    return $this->_token;
	}

    }

}
