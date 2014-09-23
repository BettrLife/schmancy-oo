<?php

namespace SchmancyOO;

/** A method combinator is basically mapreduce across a class heirarchy.  It
    calls all the defined methods of a given name, then combines the results
    using the given combinator function. */
class MethodCombinator {
	protected $iv;
	protected $op;
	protected $includeTraits;

	const IncludeTraits = true;
	const ExcludeTraits = false;

	/** Constructor, obvs.  Takes an initial value, a combinator function, and
	    whether you want to include or exclude traits from the method resolution
	    order.

	    Note that to include traits, you must subclass this class and override
	    getTraitMethodPrefix to define how you want to uniquify trait methods. */
	function __construct($iv, $op, $includeTraits = self::ExcludeTraits) {
		$this->iv = $iv;
		$this->op = $op;
		$this->includeTraits = $includeTraits;
	}

	/** To avoid the pain of dealing with name clashes, and because PHP considers
	    a method defined in a trait to have a home of the class that used the
	    trait, rather than the trait itself, names of trait methods must be
	    prefixed with something that's unique per trait.  You must override this
	    method in your local subclass to define exactly how that prefix is
	    calculated.

	    Some suggested options are the shasum of the fully-qualified class name
	    (which will be unique for all classes), or the shortName of the class
	    (which may be sufficient in your codebase). */
	protected function getTraitMethodPrefix($class) {
		// TODO: Better exception type
		throw new \BadMethodCallException("Must override getTraitMethodPrefix method to include traits");
	}

	/** Given the name of a method and a ReflectionClass, either returns a
	    ReflectionMethod if the method was defined in that specific class, or
	    returns false. */
	protected function getImmediateMethod($methodName, \ReflectionClass $class, \ReflectionClass &$instanceClass = null) {
		if ($this->includeTraits) {
			if ($class->isTrait()) {
				$methodName = $this->getTraitMethodPrefix($class).$methodName;
			}
			else {
				$instanceClass = $class;
			}
		}
		$useClass = $instanceClass ?: $class;
		$method = $useClass->hasMethod($methodName)
			? $useClass->getMethod($methodName)
			: false;
		return $method && $useClass->name === $method->getDeclaringClass()->name
			? $method
			: false;
	}

	protected function getImmediateMethodInvoker($methodName) {
		$instanceClass = null;
		return function ($class) use ($methodName, &$instanceClass) {
			return $this->getImmediateMethod($methodName, $class, $instanceClass);
		};
	}

	/** Walks the class heirarchy (see C3Linearization) and returns an array of
	    callable immediately-defined ReflectionMethods (see getImmediateMethod)
	    for that heirarchy.

	    This function is memoized. */
	protected function getDefinedMethods($class, $methodName) {
		static $cache = [];
		if (isset($cache[0+$this->includeTraits][$class->name][$methodName])) {
			return $cache[0+$this->includeTraits][$class->name][$methodName];
		}

		$classes = C3Linearization::mro($class);
		$methods = array_filter(array_map(
			$this->getImmediateMethodInvoker($methodName),
			$classes));
		foreach ($methods as $method) {
			$method->setAccessible(true);
		}
		return $cache[0+$this->includeTraits][$class->name][$methodName] = $methods;
	}

	/** Runs a method combination against a class instance (or null, for static
	    methods).

	    * $class may be either a ReflectionClass or a string, indicating the
	      class to start from.
	    * $methodName is a string indicating the method to use.
	    * $instance is either an object instance to run methods against, or null
	      for static methods.
	    * $args is an array of arguments to pass to the methods. */
	function execute($class, $methodName, $instance = null, $args = []) {
		if (is_string($class)) $class = new \ReflectionClass($class);
		$methods = $this->getDefinedMethods($class, $methodName);
		$final = $this->iv;
		foreach ($methods as $method) {
			$final = call_user_func($this->op, $final, $method->invokeArgs($instance, $args));
		}
		return $final;
	}

	/** Like execute, but uses the class of the provided instance. */
	function invoke($methodName, $instance, $args = []) {
		return $this->execute(get_class($instance), $methodName, $instance, $args);
	}
}
