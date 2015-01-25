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

class HelpersTest extends \PHPUnit_Framework_TestCase
{
	public function test_get_markups()
	{
		$markups = get_markups();

		$this->assertInstanceOf('Patron\MarkupCollection', $markups);
		$this->assertSame($markups, get_markups());
		$this->assertTrue(isset($markups['foreach']));
	}

	public function test_get_patron()
	{
		$patron = get_patron();

		$this->assertInstanceOf('Patron\Engine', $patron);
		$this->assertNotSame($patron, get_patron());
	}
}
