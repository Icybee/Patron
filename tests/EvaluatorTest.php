<?php

namespace Patron;

use BlueTihi\Context;

class EvaluatorTest extends \PHPUnit_Framework_TestCase
{
	static private $evaluator;

	static public function setupBeforeClass()
	{
		$engine = new Engine;
		self::$evaluator = new Evaluator($engine);
	}

	public function test_get_null_value()
	{
		$evaluator = self::$evaluator;
		$context = new Context([

			'value' => null

		]);

		$this->assertNull($evaluator([ 'value' => null ], 'value'));
		$this->assertNull($evaluator((object) [ 'value' => null ], 'value'));
		$this->assertNull($evaluator($context, 'value'));
	}

	/**
	 * @expectedException Patron\ReferenceError
	 */
	public function test_get_undefined_value()
	{
		$evaluator = self::$evaluator;
		$context = new Context([

			'one' => [ 'two' => [ 'three' => [] ] ]

		]);

		$evaluator($context, 'one.two.three.four.madonna()');
	}
}