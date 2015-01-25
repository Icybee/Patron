<?php

/*
 * This file is part of the Patron package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Patron;

return [

	#
	# Objects
	#

	/**
	 * Converts a value into a string.
	 */
	'to_s' => function($a) {

		if (is_array($a) || (is_object($a) && !method_exists($a, '__toString')))
		{
			return serialize($a);
		}

		return (string) $a;

	},

	#
	# Maths
	#

	'add' => function($a, $b) {

		return $a + $b;

	},

	'minus' => function($a, $b) {

		return $a - $b;

	},

	'plus' => function($a, $b) {

		return $a + $b;

	},

	'times' => function($a, $b) {

		return $a * $b;

	},

	'by' => function($a, $b) {

		return $a / $b;

	},

	#
	# Operations
	#

	'if' => function($a, $b, $c = null) {

		return $a ? $b : $c;

	},

	'or' => function($a, $b) {

		return $a ? $a : $b;

	},

	'not' => function($a) {

		return !$a;

	},

	'mod' => function($a, $b) {

		return $a % $b;

	},

	'bit' => function($a, $b) {

		return (int) $a & (1 << $b);

	},

	'greater' => function($a, $b) {

		return ($a > $b);

	},

	'smaller' => function($a, $b) {

		return ($a < $b);

	},

	'equals' => function($a, $b) {

		return ($a == $b);

	},

	'different', function($a, $b) {

		return ($a != $b);

	},

	#
	# Arrays
	#

	'split' => function($a, $b = ",") {

		return explode($b, $a);

	},

	'join' => function($a, $b = ",") {

		return implode($b, $a);

	},

	'index' => function() {

		$a = func_get_args();
		$i = array_shift($a);

		return $a[$i];

	},

	/**
	 * Returns the first element, or the first n elements, of the array. If the array is empty,
	 * the first form returns nil, and the second form returns an empty array.
	 *
	 * ```
	 * a = [ "q", "r", "s", "t" ]
	 * a.first    // "q"
	 * a.first(1) // ["q"]
	 * a.first(3) // ["q", "r", "s"]
	 * ```
	 */
	'first' => function($a, $n = null) {

		$rc = array_slice($a, 0, $n ? $n : 1);
		return $n === null ? array_shift($rc) : $rc;

	},

	#
	# String
	#

	'replace' => function($a, $b, $c = "") {

		return str_replace($b, $c, $a);

	},

	'markdown' => function($text) {

		require_once dirname(__DIR__) . '/lib/textmark/textmark.php';

		return \Markdown($text);

	}

];
