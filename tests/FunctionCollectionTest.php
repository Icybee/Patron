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

class FunctionCollectionTest extends \PHPUnit_Framework_TestCase
{
	public function test_offsetExists()
	{
		$c = new FunctionCollection;
		$this->assertFalse(isset($c['one']));
		$c['one'] = function() {};
		$this->assertTrue(isset($c['one']));
	}

	public function test_offsetSet()
	{
		$c = new FunctionCollection;
		$definition = function() {};
		$c['one'] = $definition;
		$this->assertEquals($definition, $c['one']);
	}

	/**
	 * @expectedException \Patron\FunctionNotDefined
	 */
	public function test_offsetGetUndefined()
	{
		$c = new FunctionCollection;
		$c['undefined'];
	}

	public function test_find()
	{
		$one = function() {};

		$c = new FunctionCollection([

			'one' => $one

		]);

		$this->assertSame($one, $c->find('one'));
		$this->assertSame('Patron\by_columns', $c->find('by_columns'));
		$this->assertFalse($c->find('undefined'));
	}

	public function test_call()
	{
		$c = new FunctionCollection([

			'one' => function() {

				return "one";

			}

		]);

		$this->assertEquals("one", $c->one());
	}
}
