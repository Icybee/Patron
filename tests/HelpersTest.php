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

	public function test_get_functions()
	{
		$functions = get_functions();

		$this->assertInstanceOf('Patron\FunctionCollection', $functions);
		$this->assertSame($functions, get_functions());
		$this->assertTrue(isset($functions['to_s']));
	}

	public function test_function_markdown()
	{
		$template = <<<EOT
- one
- two
- three
EOT;

		$expected = <<<EOT
<ul>
<li>one</li>
<li>two</li>
<li>three</li>
</ul>

EOT;

		$functions = get_functions();

		$this->assertEquals($expected, $functions['markdown']($template));
	}

	public function test_get_patron()
	{
		$patron = get_patron();

		$this->assertInstanceOf('Patron\Engine', $patron);
		$this->assertNotSame($patron, get_patron());
	}
}
