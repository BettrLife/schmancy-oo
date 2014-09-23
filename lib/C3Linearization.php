<?php

namespace SchmancyOO;

use SchmancyOO\C3Linearization\IndeterminateHierarchy;
use ReflectionClass;

/** Calculates a linear ordering for a class hierarchy (including traits), using
    the C3 Linearization algorithm. */
class C3Linearization {

	/** Helper method.  Determines whether the given candidate class is in a head
	    position. */
	protected static function notHead($cand, $seqs) {
		foreach ($seqs as $seq) {
			if (in_array($cand, array_slice($seq, 1), true)) {
				return true;
			}
		}
		return false;
	}

	/** Merges several hierarchy sequences into a single, unified sequence. */
	protected static function merge($seqs) {
		$res = [];
		$i = 0;
		while (count($seqs = array_values(array_filter($seqs, 'count')))) {
			$cand = null;
			foreach ($seqs as $seq) {
				$cand = $seq[0];
				if (self::notHead($cand, $seqs)) {
					$cand = null;
				} else break;
			}
			if (!$cand) throw new IndeterminateHierarchy("Could not determine appropriate hierarchy for class");
			$res[] = $cand;
			foreach ($seqs as &$seq) {
				if ($cand === $seq[0]) {
					$seq = array_slice($seq, 1);
				}
			}
		}
		return $res;
	}

	/** Determines the Method Resolution Order (MRO) for a given class, and
	    returns an array of ReflectionClass instances in that order.

	    The return value of this function includes traits.

	    This function is memoized. */
	public static function mro(ReflectionClass $class) {
		static $cache = [];
		if (isset($cache[$class->name])) return $cache[$class->name];

		$parent = $class->getParentClass();
		$traits = array_values($class->getTraits());
		$parents = $parent
			? array_merge($traits, [ $parent ])
			: $traits;
		return $cache[$class->name] = self::merge(array_merge([[$class]], array_map('self::mro', $parents), [$parents]));
	}
}
