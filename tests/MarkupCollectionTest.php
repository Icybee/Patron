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

class MarkupCollectionTest extends \PHPUnit_Framework_TestCase
{
	public function test_offsetExists()
	{
		$c = new MarkupCollection;
		$this->assertFalse(isset($c['one']));
		$c['one'] = [ function() {} ];
		$this->assertTrue(isset($c['one']));
	}

	public function test_offsetSet()
	{
		$c = new MarkupCollection;
		$definition = [ function() {} ];
		$c['one'] = $definition;
		$this->assertEquals($definition, $c['one']);
	}

	/**
	 * @expectedException \Patron\MarkupNotDefined
	 */
	public function test_offsetGetUndefined()
	{
		$c = new MarkupCollection;
		$c['undefined'];
	}
}
