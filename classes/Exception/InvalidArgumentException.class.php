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

namespace mnhcc\ml\classes\Exception {

    use \mnhcc\ml,
	\mnhcc\ml\classes,
	\mnhcc\ml\traits,
	\mnhcc\ml\interfaces;

    class InvalidArgumentException extends \InvalidArgumentException implements interfaces\Exception {

	use traits\Exception;

	const TYPE_BOOLEAN = 'boolean';
	const TYPE_INTEGER = 'integer';
	const TYPE_DOUBLE = 'double';
	const TYPE_STRING = 'string';
	const TYPE_ARRAY = 'array';
	const TYPE_OBJECT = 'object';
	const TYPE_RESOURCE = 'resource';
	const TYPE_NULL = NULL;
	
	public $type = self::TYPE_NULL;
	
	protected static $_defMessage = 'Argument mus from type "%s" in %s()';
	
	public static function getDefMessage() {
	    $args = \func_get_args();
	    $args = classes\ArrayHelper::addBefore($args, self::$_defMessage);
	    return \call_user_func_array('sprintf', $args);
	}
	
	public function __construct($message = '', $code = 0, $previous = null) {
	    
	    switch ($message) {
		case self::TYPE_ARRAY:
		case self::TYPE_BOOLEAN:
		case self::TYPE_DOUBLE:
		case self::TYPE_INTEGER:
		case self::TYPE_NULL:
		case self::TYPE_OBJECT:
		case self::TYPE_RESOURCE:
		case self::TYPE_STRING:
		    $this->type = $message;
		    $caller = debug_backtrace()[1];
		    $class = classes\ArrayHelper::get('class', $caller, false);
		    $function = (($class) ? $class.'::' : '') . classes\ArrayHelper::get('function', $caller, '__main__');
		    $message = \sprintf(self::getDefMessage($this->getType(), $function));
		    break;
		default:
		    break;
	    }
	    parent::__construct($message, $code, $previous);
	}
	
	public function getType() {
	    if(self::TYPE_NULL == $this->type) {
		return \gettype($this->type);
	    }
	    return (string) $this->type;
	}
	
	//public function g
    }

}