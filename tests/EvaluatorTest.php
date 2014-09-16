<?php

namespace Patron;

use BlueTihi\Context;

class EvaluatorTest extends \PHPUnit_Framework_TestCase
{
	public function test_get_null_value()
	{
		$engine = new Engine;
		$evaluator = new Evaluator($engine);
		$context = new Context([

			'value' => null

		]);

		$this->assertNull($evaluator([ 'value' => null ], 'value'));
		$this->assertNull($evaluator((object) [ 'value' => null ], 'value'));
		$this->assertNull($evaluator($context, 'value'));
	}
}