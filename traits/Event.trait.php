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

namespace mnhcc\ml\traits {

    use \mnhcc\ml\classes, 
	\mnhcc\ml\classes\Exception,
	\mnhcc\ml\classes\EventManager;

/*
     * Caller
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     */

    trait Event {

	/**
	 *
	 * @var \mnhcc\ml\classes\EventParms 
	 */
	protected $_eventParms = null;

	/**
	 *
	 * @var array 
	 */
	protected $_parms = [];

	/**
	 *
	 * @var string 
	 */
	protected $_eventName = '';

	protected function setEventParms(classes\EventParms $eParms) {
	    $this->_eventParms = $eParms;
	    return $this;
	}
	protected function getEventParms() {
	    return $this->_eventParms;
	}
	protected function setEventName($eventName) {
	    $this->_eventName = EventManager::cleanEventName($eventName, true);
	    return $this;
	}

	public function getEventName() {
	    return $this->_eventName;
	}

	public function getParms() {
	    return $this->_parms;
	}

	public function setParms($parms) {
	    if (!Helper::isArray($parms)) {
		throw new Exception\InvalidArgumentException(__CLASS__ . '::' . __FUNCTION__ . 'no Array given', 0);
	    }
	    $this->_parms = $parms;
	    $this->toEventParms($this->_parms);
	    return $this;
	}

	public function addParms($parms, $overide = false) {
	    if (!Helper::isArray($parms)) {
		throw new Exception\InvalidArgumentException(Exception\InvalidArgumentException::TYPE_ARRAY);
	    }
	    if ($overide) {
		$this->_parms = Helper::arrayMerge($this->_parms, $parms);
	    } else {
		$this->_parms = Helper::arrayMerge($parms, $this->_parms);
	    }
	    $this->toEventParms($this->_parms);
	    return $this;
	}

	protected function toEventParms($parms) {
	    if (!Helper::isArray($parms)) {
		throw new Exception\InvalidArgumentException(Exception\InvalidArgumentException::TYPE_ARRAY);
	    }
	    if ($this->getEventParms() !== null) {
		foreach ($parms as $key => $value) {
		    $this->getEventParms()->set($key, $value);
		}
	    }
	}

    }

}
