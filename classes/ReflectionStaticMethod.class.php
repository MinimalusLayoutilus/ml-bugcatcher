<?php

namespace mnhcc\ml\classes;

use \mnhcc\ml\classes\Exception as exception;
use \mnhcc\ml\interfaces as interfaces;
use \mnhcc\ml\traits as traits; {

    /**
     * ReflectionObjectMethod is a warpper for methods of objects, 
     * you can call method dynamical from the name (string)
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class ReflectionStaticMethod extends \ReflectionMethod implements interfaces\MNHcC {

        use traits\MNHcC;

        public function __construct($class, $name) {
            parent::__construct($class, $name);
            if (!$this->isStatic()) {
                throw new Exception('Method is not Static');
            }
        }

        public function __invoke() {
            if (func_num_args() > 0) {
                return self::invokeArgs(func_get_args());
            } else {
                return self::invoke();
            }
        }

	/**
	 * Invokes the bound static method with the given args array.  Two
	 * call shapes are accepted to bridge the parent's PHP-8 signature
	 * (`?object $object, array $args`) and the framework's legacy
	 * one-arg form:
	 *   - new / parent-compatible:  $rsm->invokeArgs(null, [arg1, arg2])
	 *   - legacy framework form:    $rsm->invokeArgs([arg1, arg2])
	 * The first parameter is ignored when it is not null/object — for
	 * static methods the bound class is already known via the
	 * `($class, $name)` constructor and parent::invokeArgs(null, ...)
	 * is what PHP wants on every supported runtime.
	 *
	 * @return mixed the result of method
	 */
        #[\ReturnTypeWillChange]
        public function invokeArgs($objectOrArgs = null, array $args = []) {
            if (is_array($objectOrArgs)) {
                // Legacy single-array form: caller passed args as first param.
                $args = $objectOrArgs;
            }
            return parent::invokeArgs(null, $args);
        }
	
	#[\ReturnTypeWillChange]
	public function getClosureScopeClass() {
	    parent::getClosureScopeClass();
	}

        /**
         * Invokes the bound static method with the given args.
         * Signature is variadic to match parent's PHP-8
         * (`?object $object, mixed ...$args`), but the framework's
         * historical API treats every argument as a method arg —
         * the class is already bound via the `($class, $name)` ctor,
         * so the parent's `$object` slot is meaningless for static
         * methods.  Whatever the caller passes as the first argument
         * is shifted into the args list.
         *
         * @return mixed the method result.
         */
        #[\ReturnTypeWillChange]
        public function invoke($object = null, ...$args) {
            $callArgs = $args;
            if (\func_num_args() > 0) {
                \array_unshift($callArgs, $object);
            }
            return parent::invokeArgs(null, $callArgs);
        }
	public static function ___onLoaded() {
	    return null;
	}

    }

}
