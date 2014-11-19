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

class PatronTest extends \PHPUnit_Framework_TestCase
{
	public function test_evaluate()
	{
		$engine = new Engine;
		$engine->context['one'] = [ 'two' => [ 'three' => 3 ] ];
		$this->assertEquals(3, $engine->evaluate('one.two.three'));
		$this->assertNull($engine->evaluate('one.two.four', true));

		try
		{
			$engine->evaluate('one.two.four');

			$this->fail('Expected Patron\ReferenceError');
		}
		catch (\Exception $e)
		{
			$this->assertInstanceOf('Patron\ReferenceError', $e);
		}
	}
}
