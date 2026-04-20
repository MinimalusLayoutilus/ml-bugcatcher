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
	mnhcc\ml\traits;

    /**
     * Description of Event
     * 
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class EventObjectMethod extends ReflectionObjectMethod implements interfaces\Event, interfaces\MNHcC, interfaces\Instances {

	use traits\Event,
	    traits\MNHcC,
	    traits\NoInstances;

	/**
	 * @var  \mnhcc\ml\classes\ReflectionClass 
	 */
	protected $_object = null;

	public function __construct($class, $name, $event) {
	    $this->_eventName = EventManager::cleanEventName($event);
	    parent::__construct($class, $name);
	    $this->setObject($class);
	}

	public function setObject($object) {
	    if (\is_object($object)) {
		$this->_object = $object;
	    } elseif ($object == null) {
		$arg = func_get_args();
		if (ArrayHelper::get(0, $arg, false)) {
		    ArrayHelper::shift($arg);
		    $this->_object = (new ReflectionClass($this->getClass()))->getInstanceArgs($arg);
		}
	    }

	    return $this;
	}

	public function getObject() {
	    if ($this->isStatic()) {
		return null;
	    }
	    return $this->_object;
	}

	/**
	 * 
	 * @param \mnhcc\ml\classes\EventParms $eparm
	 * @return mixed
	 */
	protected function raise(\mnhcc\ml\classes\EventParms $eparm) {
	    $this->toEventParms($this->_parms);
	    return $this->invoke($this->getObject(), $eparm);
	}

    }

}