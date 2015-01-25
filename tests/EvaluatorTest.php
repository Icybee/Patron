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

use BlueTihi\Context;

class EvaluatorTest extends \PHPUnit_Framework_TestCase
{
	private $evaluator;

	public function setup()
	{
		/* @var $markups \Patron\MarkupCollection */
		$markups = $this
			->getMockBuilder('Patron\MarkupCollection')
			->getMock();

		/* @var $functions \Patron\FunctionCollection */
		$functions = $this
			->getMockBuilder('Patron\FunctionCollection')
			->getMock();

		$engine = new Engine($markups, $functions);
		$this->evaluator = new Evaluator($engine);
	}

	public function test_get_null_value()
	{
		$evaluator = $this->evaluator;
		$context = new Context([

			'value' => null

		]);

		$this->assertNull($evaluator([ 'value' => null ], 'value'));
		$this->assertNull($evaluator((object) [ 'value' => null ], 'value'));
		$this->assertNull($evaluator($context, 'value'));
	}

	/**
	 * @expectedException \Patron\ReferenceError
	 */
	public function test_get_undefined_value()
	{
		$evaluator = $this->evaluator;
		$context = new Context([

			'one' => [ 'two' => [ 'three' => [] ] ]

		]);

		$evaluator($context, 'one.two.three.four.madonna()');
	}

	public function test_get_undefined_value_silent()
	{
		$evaluator = $this->evaluator;
		$context = new Context([

			'one' => [ 'two' => [ 'three' => [] ] ]

		]);

		$evaluator($context, 'one.two.three.four.madonna()', true);
	}
}
