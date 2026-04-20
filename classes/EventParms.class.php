<?php

/*
 * Copyright (C) 2013 Michael Hegenbarth (carschrotter) <mnh@mn-hegenbarth.de>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

namespace mnhcc\ml\classes {

    use \mnhcc\ml\classes\Exception,
	\mnhcc\ml\interfaces,
	\mnhcc\ml\traits;
    /**
     * Description of EventParms
     *
     * @author carschrotter
     */
    class EventParms extends MNHcC implements interfaces\Parameters {

	use traits\NoInstances;
	
	protected $_parms = [];

	public function __construct($parms = []) {
	    if (!ArrayHelper::isArray($parms)) {
		throw new Exception\InvalidArgumentException('$parms is not a Array', 0);
	    }
	    $this->_parms = $parms;
	}

	public function getParms() {
	    return $this->_parms;
	}

	public function getEvent() {
	    return $this->_parms['event'];
	}

	public function setEvent($event) {
	    if( !ArrayHelper::in(debug_backtrace()[1]['class'], [__CLASS__,'mnhcc\\ml\\classes\\EventManager'] ) ){
		throw Exception('Trying to invoke protected method '.__CLASS__.'::setEvent() from scope '. debug_backtrace()[1]['class']);
	    } 
	    $this->_parms['event'] = $event;
	}

	public function set($key, $value) {
	    $this->_parms[$key] = $value;
	}

	public function get($key, $default = null, $filter = false) {
	    $parms = $this->getParms();
	    $answer = ((isset($parms[$key])) ? $parms[$key] : $default );
	    return (is_string($answer) && $filter) ? Filter::html($answer) : $answer;
	}

	/**
	 * 
	 * @param string $key
	 * @param mixed $check number or string
	 * @return bool
	 */
	public function is($key, $check = true, $method = false) {
	    switch ($method) {
		case self::IS_CASE_SENSETIV:
		    if (is_string($this->get($key, false)) && is_string($check)) {
			return (bool) (strtolower($this->get($key)) === strtolower($check));
		    }
		    break;
		case self::IS_ISSET:
		    return ($this->get($key, self::NOTFOUND) !== self::NOTFOUND);
		default:
		    return (bool) ( $this->get($key) == $check);
		    break;
	    }
	    return false;
	}

    }

}