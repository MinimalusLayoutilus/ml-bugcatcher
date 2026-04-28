<?php

namespace mnhcc\ml\classes;

use \mnhcc\ml\classes\Exception as exception;
use \mnhcc\ml\interfaces as interfaces;
use \mnhcc\ml\traits as traits;
{

    /**
     * ReflectionObjectMethod is a warpper for methods of objects, 
     * you can call method dynamical from the name (string)
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class ReflectionObjectMethod extends \ReflectionMethod implements interfaces\MNHcC {

        use traits\MNHcC;

        /**
         * is a default value
         * vor invoke method
         */

        const defaultArg = '{defaultArg : "true", secure : "Ay0keRT1l8"}';

        protected $object;

        /**
         * @param object $object
         * @param string $name
         * @throws \Exception
         */
        public function __construct($object, $name) {
            if (!is_object($object)) {
                throw new Exception\InvalidArgumentException('Argument 1 passed to ' . __CLASS__ . '::' . __METHOD__ . ' must be an object , ' . gettype($object) . ' given', -1);
            }
            try {
                parent::__construct($object, $name);
            } catch (\Exception $exc) {
                throw new Exception\ReflectionMethodException($object, $name, $exc->getCode(), $exc);
            }
            $this->object = $object;
        }

	/**
	 * 
	 * @return mixed the method result.
	 */
        public function __invoke() {
            return self::invokeArgs($this->object, func_get_args());
        }

        /**
         * invokes a static method on the class of the object
         * @return mixed the result of method
         */
        public function invokeStatic() {
            return self::invokeArgs(null, func_get_args());
        }

        /**
         * Invokes the bound method on `$this->object` with the given
         * args.  Signature is variadic to match parent's PHP-8
         * (`?object $object, mixed ...$args`), but the framework's
         * historical API treats every argument as a method arg —
         * ROM is already bound to `$this->object`, so the parent's
         * `$object` slot has no semantic role here.  Whatever the
         * caller passes as the first argument is shifted into the
         * args list.
         *
         * @return mixed the method result.
         */
        #[\ReturnTypeWillChange]
        public function invoke($object = null, ...$args) {
            $callArgs = $args;
            if (\func_num_args() > 0) {
                \array_unshift($callArgs, $object);
            }
            return parent::invokeArgs($this->object, $callArgs);
        }

        /**
         * set another object of the same type.
         * @param object $object
         * @return boolean 
         */
        public function setObject($object) {
            if (is_a($object, $this->class)) {
                $this->object = $object;
                return true;
            } else {
                //@ToDo implement a exeption
                return false;
            }
        }

    }

}
