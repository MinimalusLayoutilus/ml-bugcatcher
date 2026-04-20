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
     * Central event dispatcher — dispatches named events to registered listeners.
     *
     * @author carschrotter
     */
    abstract class EventManager {

	/**
	 * Registered listeners keyed by normalised event name.
	 * @var array
	 */
	static protected $_events = [];

	/**
	 * Raises a named event, notifying all registered listeners.
	 * @param string     $name  Event name (leading "on" is stripped automatically).
	 * @param EventParms $parms Parameter bag passed to each listener.
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

	/**
	 * Normalises an event name: strips leading "on" prefix and applies ucfirst.
	 * @param string $name   Raw event name.
	 * @param bool   $asKey  Return all-lowercase for use as an array key.
	 * @return string
	 */
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