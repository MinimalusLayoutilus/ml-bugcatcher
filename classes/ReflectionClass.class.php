<?php

namespace mnhcc\ml\classes {

    use \mnhcc\ml\classes\Exception as Exception;
    use \mnhcc\ml\interfaces;
    use \mnhcc\ml\traits;

    /**
     * Description of ReflectionClass
     *
     * @author Michael Hegenbarth (carschrotter)
     * @package MinimalusLayoutilus	 
     * @copyright (c) 2013, Michael Hegenbarth
     */
    class ReflectionClass extends \ReflectionClass implements interfaces\MNHcC, interfaces\Instances {

	use traits\MNHcC,
	    traits\NoInstances;
	
	public function __construct($argument) {
	    /**
	     * @todo self keyword on ReflectionClass
	     */
	    if(\is_string($argument) && $argument == 'self') {
		Helper::whereIsSelf();
	    }
	    parent::__construct($argument);
	}
	/**
	 * replace a CaMeL Case Class 
	 * @param string $class
	 * @param string $to_class
	 * @param bool $contains_in_namespace
	 * @param bool $namespace_limit
	 * @return string the new classname
	 */
	public static function replacedMasterClass($class, $to_class, $contains_in_namespace = false) {
	    $namespaces = ArrayHelper::explode(NSS, $class);
	    $names = \preg_split('/(?<=.)(?=\p{Lu}\P{Lu})|(?<=\P{Lu})(?=\p{Lu})/U', ArrayHelper::pop($namespaces));
	    $namespace = ArrayHelper::implode($namespaces, NSS);
	    $masterClass = ArrayHelper::shift($names);
	    if ($contains_in_namespace) {
		if ($namespace != '') {
		    $regex = RegEx::getInstance($masterClass);
		    $regex->isAutoMask(true);
		    return BootstrapHandler::makeClassName($regex->replace($to_class, $namespace), \ucfirst($to_class) . implode($names));
		} else {
		    return NSS . ucfirst($to_class) . ArrayHelper::implode($names);
		}
	    } else {
		return ucfirst($to_class) . implode($names);
	    }
	}

	/**
	 * 
	 * @param string $name the method name
	 * @return mixed
	 */
	public function callStatic($name) {
	    $args = func_get_args();
	    array_shift($args);
	    $callee = $this->getMethod($name, BootstrapHandler::addRootNamespace('ReflectionStaticMethod'));
	    return $callee->invokeArgs($args);
	}

	public function getReplacedMasterClass($toClass, $namespace = false) {
	    return self::replacedMasterClass($this->getName(), $toClass, $namespace);
	}

	/**
	 * Gets a <b>ReflectionMethod</b> for a class method.
	 * @param string $name <p>
	 * The method name to reflect.
	 * </p>
	 * @param string $class
	 * @return ReflectionMethod|null
	 */
	public function getMethod($name, $class = null) {
	    if ($class !== null) {
		if (Helper::classExists($class, false, true) && is_subclass_of($class, 'ReflectionMethod')) {
		    return new $class($this->getName(), $name);
		}
	    } else {
		return parent::getMethod($name);
	    }
	    return null;
	}

	public function getConstants($only_framework_const = false, $prefix_filter = false) {
	    $constants = parent::getConstants();
	    if ($only_framework_const) {
		$constants = $this->getFrameworkConstants($constants);
	    }
	    if ($prefix_filter) {
		$constants = $this->getPrefixConstants($prefix_filter, $constants);
	    }
	    return $constants;
	}

	public function getFrameworkConstants($constants = null) {
	    $constants = ($constants === null) ? parent::getConstants() : $constants;
	    $result = [];
	    foreach ($constants as $name => $value) {
		if (Bootstrap::isMLConst($value)) {
		    $result[$name] = $value;
		}
	    }
	    return $result;
	}

	public function getPrefixConstants($prefix_filter, $constants = null) {
	    $constants = ($constants === null) ? parent::getConstants() : $constants;
	    $result = [];
	    $prefix_filter = (string) $prefix_filter;
	    foreach ($constants as $name => $value) {
		if (strpos($name, $prefix_filter) === 0) {
		    $result[$name] = $value;
		}
	    }
	    return $result;
	}

	public static function ___onLoaded() {
	    return null;
	}

//		public function getNameCleened() {
//			$name = $this->getName();
//			$parts = explode('\\', $name);
//			$class = array_pop($parts);
//			return $class;
//		}
//		public static function autoload($klasse) {
//			if (self::$basisPfad === null)
//				self::$basisPfad = dirname(__FILE__);
//			if (substr($klasse, 0, 8) !== "SELFHTML")
//				return;
//			if (strpos($klasse, '.') !== false || strpos($klasse, '/') !== false || strpos($klasse, '\\') !== false || strpos($klasse, ':') !== false) {
//				return;
//			}
//
//			$pfad = self::$basisPfad . DIRECTORY_SEPARATOR .
//					join(DIRECTORY_SEPARATOR, $teile) . '.php';
//			if (!file_exists($pfad))
//				return;
//			include_once $pfad;
//		}
    }

}