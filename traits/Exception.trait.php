<?php

namespace mnhcc\ml\traits;
use mnhcc\ml\classes as classes;
{

	/**
	 * @
	 * @author Michael Hegenbarth (carschrotter)
	 * @package MinimalusLayoutilus	 
	 */
	trait Exception {
		use MNHcC;
		public function jsonSerialize() {
			if (classes\Router::isDebug()) {
				$value = (object) \get_object_vars($this);
				unset($value->xdebug_message);
				$value->trace = $this->getTrace();
			} else {
				$value = $this->getMessage();
			}
			return $value;
		}
		
		public function __toString() {
			return $this->getMessage();
		}
	}

}