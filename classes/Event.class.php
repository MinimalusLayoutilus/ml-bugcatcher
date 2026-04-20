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

    use mnhcc\ml\interfaces,
	mnhcc\ml\traits as traits;

    /**
     * Description of Event
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class Event implements interfaces\Event {

	use traits\Event;

	/**
	 * @var callable
	 */
	protected $_callback = null;

	/**
	 * 
	 * @param callable $callback
	 * @param string $event the event name
	 */
	public function __construct($callback, $event) {
	    $this->setEventName($event);
	    if (is_callable($callback)) {
		$this->_callback = $callback;
	    } else {
		throw new \InvalidArgumentException('$callback (' . gettype($callback) . ') is not callable!');
	    }
	}

	protected function getCallback() {
	    return $this->_callback;
	}

	/**
	 * 
	 * @param \mnhcc\ml\classes\EventParms $eparm
	 * @return type
	 */
	public function raise(EventParms $eparm, $index) {
	    $this->setEventParms($eparm);
	    $this->toEventParms($this->_parms);
	    return \call_user_func($this->getCallback(), $eparm);
	}

    }

}