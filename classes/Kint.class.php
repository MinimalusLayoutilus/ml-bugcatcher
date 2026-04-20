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

    use \mnhcc\ml\traits,
	\mnhcc\ml\interfaces;

    class Kint extends \Kint implements interfaces\MNHcC {

	use traits\MNHcC;

	/**
	 *
	 * @var \Closure 
	 */
	protected static $_css;

	/**
	 *
	 * @var \Closure 
	 */
	protected static $_wrapStart;

	/**
	 *
	 * @var \Closure 
	 */
	protected static $_wrapEnd;

	/**
	 *
	 * @var bool 
	 */
	protected static $func_init;
	public static $defaultModifier;

	/**
	 *
	 * @var  \mnhcc\ml\classes\ReflectionClass 
	 */
	protected static $_parent;

	/**
	 * dump information about variables
	 *
	 * @param mixed $data
	 *
	 * @return void|string
	 */
	public static function dump($data = null) {

	    if (!Kint::enabled())
		return;
	    if (!self::$func_init) {
		$Kint_Decorators_Rich = new ReflectionClass('Kint_Decorators_Rich');

		$cssrfl = $Kint_Decorators_Rich->getMethod('_css', BootstrapHandler::addRootNamespace('ReflectionStaticMethod'));
		//self::$cssrfl->setAccessible(true);
		self::$_css = $cssrfl->getClosure();
		self::$_css->bindTo(null, 'Kint_Decorators_Rich');

		$wrapStartrfl = $Kint_Decorators_Rich->getMethod('_wrapStart', BootstrapHandler::addRootNamespace('ReflectionStaticMethod'));
		//self::$wrapStartrfl->setAccessible(true);
		self::$_wrapStart = $wrapStartrfl->getClosure();
		self::$_wrapStart->bindTo(null, 'Kint_Decorators_Rich');

		$wrapEndrfl = $Kint_Decorators_Rich->getMethod('_wrapEnd', BootstrapHandler::addRootNamespace('ReflectionStaticMethod'));
		//self::$wrapEndrfl->setAccessible(true);
		self::$_wrapEnd = $wrapEndrfl->getClosure();
		self::$_wrapEnd->bindTo(null, 'Kint_Decorators_Rich');

		self::$func_init = true;
	    }

	    # find caller information
	    $trace = debug_backtrace();
	    list( $names, $modifier, $callee, $previousCaller ) = self::_getPassedNames($trace);
	    $modifier = ($modifier) ? $modifier : self::defaultModifier();
	    if ($names === array(null) && func_num_args() === 1 && $data === 1) {
		$call = reset($trace);
		if (!isset($call['file']) && isset($call['class']) && $call['class'] === __CLASS__) {
		    array_shift($trace);
		    $call = reset($trace);
		}

		while (isset($call['file']) && $call['file'] === __FILE__) {
		    array_shift($trace);
		    $call = reset($trace);
		}

		self::trace($trace);
		return;
	    }

	    # process modifiers: @, + and -
	    switch ($modifier) {
		case '-':
		    self::$_firstRun = true;
		    while (ob_get_level()) {
			ob_end_clean();
		    }
		    break;

		case '!':
		    self::$expandedByDefault = true;
		    break;
		case '+':
		    $maxLevelsOldValue = self::$maxLevels;
		    self::$maxLevels = false;
		    break;
		case '@':
		    $firstRunOldValue = self::$_firstRun;
		    self::$_firstRun = true;
		    break;
	    }

	    $data = func_num_args() === 0 ? array("[[no arguments passed]]") : func_get_args();
	    $output = \call_user_func(self::$_css);
	    $output .= \call_user_func(self::$_wrapStart, $callee);

	    foreach ($data as $k => $argument) {
		$output .= self::_dump($argument, $names[$k]);
	    }

	    $output .= \call_user_func(self::$_wrapEnd, $callee, $previousCaller);

	    // $output .= \Kint_Decorators_Rich::_wrapEnd($callee, $previousCaller);

	    self::$_firstRun = false;

	    switch ($modifier) {
		case '+':
		    self::$maxLevels = $maxLevelsOldValue;
		    echo $output;
		    break;
		case '@':
		    self::$_firstRun = $firstRunOldValue;
		    return $output;
		    break;
		default:
		    echo $output;
		    break;
	    }

	    return '';
	}

	protected static function _getPassedNames($trace) {
	    static $_getPassedNames;
	    if (!$_getPassedNames) {
		$class = new ReflectionClass(__CLASS__);
		$parent = $class->getParentClass();
		$_getPassedNames = $parent->getMethod('_getPassedNames');
		$_getPassedNames->setAccessible(true);
		$rr = $_getPassedNames->getClosure();
		$rr->bindTo(null, __CLASS__);
	    }
	    return $_getPassedNames->invoke(null, $trace);
	}

//        protected static function _getPassedNames($trace) {
//            return parent::_getPassedNames($trace);
//        }

	public static function defaultModifier($value = null) {
	    # act both as a setter...
	    if (func_num_args() > 0) {
		self::$defaultModifier = $value;
		return;
	    }

	    # ...and a getter
	    return self::$defaultModifier;
	}

	public static function _init() {
	    return parent::_init();
	}

	public static function ___onLoaded() {
	    
	}

    }

}
