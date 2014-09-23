<?php

namespace SchmancyOO;
use PHPUnit_Framework_TestCase, ReflectionClass;

class MethodCombinatorTest extends PHPUnit_Framework_TestCase {
	function provideStaticTest() {
		$_ = __NAMESPACE__.'\\MCT_';
		$classRet = [
			[ 'Root', ['Root'] ],
			[ 'Sub1', ['Root'] ],
			[ 'Sub1Sub', ['Sub1Sub', 'Root'] ],
			[ 'Sub2', ['Sub2', 'Root'] ],

			[ 'Trait1', ['Trait1'] ],
			[ 'Trait2', [] ],
			[ 'SubTrait', ['SubTrait', 'Trait1'] ],

			[ 'SubWithTrait1', [ 'SubWithTrait1', 'Trait1', 'Sub2', 'Root' ] ],
			[ 'SubWithTrait2', [ 'Root' ] ],
			[ 'SubWithTrait3', [ 'SubWithTrait3', 'SubTrait', 'Trait1', 'Root' ] ],
		];
		$addPrefix = function ($x) use ($_) { return $_.$x; };
		return array_map(function ($x) use ($addPrefix) {
				return [ $addPrefix($x[0]), $x[1] ];
			}, $classRet);
	}

	/** @dataProvider provideStaticTest */
	public function testStaticCombinator($class, $expectedVal) {
		$mc = new MCT_MC([], 'array_merge', MethodCombinator::IncludeTraits);
		$this->assertEquals($expectedVal, $mc->execute($class, 'static_merge', null, []));
	}

	function provideInstanceTest() {
		$_ = __NAMESPACE__.'\\MCT_';
		$classRet = [
			[ 'Root', ['Root'] ],
			[ 'Sub1', ['Root'] ],
			[ 'Sub1Sub', ['Sub1Sub', 'Root'] ],
			[ 'Sub2', ['Sub2', 'Root'] ],

			[ 'SubWithTrait1', [ 'SubWithTrait1', 'Sub2', 'Root' ] ],
			[ 'SubWithTrait2', [ 'Root' ] ],
			[ 'SubWithTrait3', [ 'SubWithTrait3', 'Root' ] ],
		];
		$addPrefix = function ($x) use ($_) { return $_.$x; };
		return array_map(function ($x) use ($addPrefix) {
				return [ $addPrefix($x[0]), $x[1] ];
			}, $classRet);
	}

	/** @dataProvider provideInstanceTest */
	public function testInstanceCombinator($class, $expectedVal) {
		$mc = new MCT_MC([], 'array_merge', MethodCombinator::ExcludeTraits);
		$uniq = time();
		$instance = new $class($uniq);
		$this->assertEquals(array_merge($expectedVal, [$uniq]), $mc->execute($class, 'merge', $instance, []));
	}

	function provideTraitInstanceTest() {
		$_ = __NAMESPACE__.'\\MCT_';
		$classRet = [
			[ 'Root', ['Root'] ],
			[ 'Sub1', ['Root'] ],
			[ 'Sub1Sub', ['Sub1Sub', 'Root'] ],
			[ 'Sub2', ['Sub2', 'Root'] ],

			[ 'SubWithTrait1', [ 'SubWithTrait1', 'Trait1', 'Sub2', 'Root' ] ],
			[ 'SubWithTrait2', [ 'Root' ] ],
			[ 'SubWithTrait3', [ 'SubWithTrait3', 'SubTrait', 'Trait1', 'Root' ] ],
		];
		$addPrefix = function ($x) use ($_) { return $_.$x; };
		return array_map(function ($x) use ($addPrefix) {
				return [ $addPrefix($x[0]), $x[1] ];
			}, $classRet);
	}

	/** @dataProvider provideTraitInstanceTest */
	public function testTraitInstanceCombinator($class, $expectedVal) {
		$mc = new MCT_MC([], 'array_merge', MethodCombinator::IncludeTraits);
		$uniq = time();
		$instance = new $class($uniq);
		$got = $mc->execute($class, 'merge', $instance, []);
		$this->assertEquals(array_merge($expectedVal, [$uniq]), $got);
	}
}

class MCT_MC extends MethodCombinator {
	protected function getTraitMethodPrefix($class) {
		return $class->getShortName().'_';
	}
}

class MCT_Root {
	protected $uniq;

	function __construct($uniq) { $this->uniq = $uniq; }
	public static function static_merge() { return ['Root']; }
	public function merge() { return ['Root', $this->uniq]; }
}
class MCT_Sub1 extends MCT_Root {}
class MCT_Sub1Sub extends MCT_Sub1 {
	public static function static_merge() { return ['Sub1Sub']; }
	public function merge() { return ['Sub1Sub']; }
}
class MCT_Sub2 extends MCT_Root {
	public static function static_merge() { return ['Sub2']; }
	public function merge() { return ['Sub2']; }
}

trait MCT_Trait1 {
	public static function MCT_Trait1_static_merge() { return ['Trait1']; }
	public function MCT_Trait1_merge() { return ['Trait1']; }
}
trait MCT_Trait2 {}
trait MCT_SubTrait {
	use MCT_Trait1;
	public static function MCT_SubTrait_static_merge() { return ['SubTrait']; }
	public function MCT_SubTrait_merge() { return ['SubTrait']; }
}

class MCT_SubWithTrait1 extends MCT_Sub2 {
	use MCT_Trait1;
	public static function static_merge() { return ['SubWithTrait1']; }
	public function merge() { return ['SubWithTrait1']; }
}
class MCT_SubWithTrait2 extends MCT_Sub1 {
	use MCT_Trait2;
}
class MCT_SubWithTrait3 extends MCT_SubWithTrait2 {
	use MCT_SubTrait;
	public static function static_merge() { return ['SubWithTrait3']; }
	public function merge() { return ['SubWithTrait3']; }
}
