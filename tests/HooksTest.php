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

class HooksTest extends \PHPUnit_Framework_TestCase
{
	public function test_markup_if()
	{
		$engine = new Engine;
		$template = <<<EOT
<p:if test="undefined">
FAILURE
</p:if>
EOT;
		$this->assertEmpty($engine($template));
	}

	public function test_markup_choose()
	{
		$engine = new Engine;
		$template = <<<EOT
<p:choose>
	<p:when test="undefined">FAILURE</p:when>
	<p:otherwise>SUCCESS</p:otherwise>
</p:choose>
EOT;
		$this->assertEquals('SUCCESS', $engine($template));
	}
}
