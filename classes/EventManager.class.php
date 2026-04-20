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

    /**
     * Description of EventManager
     *
     * @author carschrotter
     */
    abstract class EventManager {

	static protected $_events = [];

	/**
	 * 
	 * @param string $name
	 * @param \mnhcc\ml\classes\EventParms $parms
	 */
	static public function raise($name, EventParms $parms) {
	    $cName = self::cleanEventName($name);
	    $parms->setEvent($cName);
	    if (isset(self::$_events[$cName])) {
		foreach (self::$_events[$cName] as $index => $event) {
		    $event->raise($parms, $index);
		}
	    }
	}

	static public function cleanEventName($name, $asKey = false) {
	    $cleanEventName = \preg_replace("~^on~i", '', $name);
	    if($asKey){return \strtolower($cleanEventName);}
	    return \ucfirst($cleanEventName);
	}

	/**
	 * 
	 * @param \mnhcc\ml\classes\Event $event
	 */
	static public function register(Event $event) {
	    return self::$_events[$event->getEventName()][] = $event;
	}

    }

}