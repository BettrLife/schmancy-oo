<?php

namespace SchmancyOO;
use PHPUnit_Framework_TestCase, ReflectionClass;

class C3LinearizationTest extends PHPUnit_Framework_TestCase {
	function provideTestMRO() {
		$_ = __NAMESPACE__.'\\C3LT_';
		$classMRO = [
			[ 'Root', ['Root'] ],
			[ 'Sub1', ['Sub1', 'Root'] ],
			[ 'Sub1Sub', ['Sub1Sub', 'Sub1', 'Root'] ],
			[ 'Sub2', ['Sub2', 'Root'] ],

			[ 'Trait1', ['Trait1'] ],
			[ 'Trait2', ['Trait2'] ],
			[ 'SubTrait', ['SubTrait', 'Trait1'] ],

			[ 'SubWithTrait1', [ 'SubWithTrait1', 'Trait1', 'Sub2', 'Root' ] ],
			[ 'SubWithTrait2', [ 'SubWithTrait2', 'Trait2', 'Sub1', 'Root' ] ],
			[ 'SubWithTrait3', [ 'SubWithTrait3', 'SubTrait', 'Trait1', 'SubWithTrait2', 'Trait2', 'Sub1', 'Root' ] ],
		];
		$addPrefix = function ($x) use ($_) { return $_.$x; };
		return array_map(function ($x) use ($addPrefix) {
				return [ $addPrefix($x[0]), array_map($addPrefix, $x[1]) ];
			}, $classMRO);
	}

	/** @dataProvider provideTestMRO */
	public function testMRO($class, $expectedMRO) {
		$gotMRO = C3Linearization::mro(new ReflectionClass($class));
		$this->assertEquals($expectedMRO, array_map(function ($x) { return $x->getName(); }, $gotMRO));
	}
}

class C3LT_Root {}
class C3LT_Sub1 extends C3LT_Root {}
class C3LT_Sub1Sub extends C3LT_Sub1 {}
class C3LT_Sub2 extends C3LT_Root {}

trait C3LT_Trait1 {}
trait C3LT_Trait2 {}
trait C3LT_SubTrait { use C3LT_Trait1; }

class C3LT_SubWithTrait1 extends C3LT_Sub2 { use C3LT_Trait1; }
class C3LT_SubWithTrait2 extends C3LT_Sub1 { use C3LT_Trait2; }
class C3LT_SubWithTrait3 extends C3LT_SubWithTrait2 { use C3LT_SubTrait; }
