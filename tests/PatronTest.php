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
	/**
	 * @var MarkupCollection
	 */
	private $markups;

	/**
	 * @var FunctionCollection
	 */
	private $functions;

	public function setup()
	{
		$this->markups = $this
			->getMockBuilder('Patron\MarkupCollection')
			->getMock();

		$this->functions = $this
			->getMockBuilder('Patron\FunctionCollection')
			->getMock();
	}

	public function test_evaluate()
	{
		$engine = new Engine($this->markups, $this->functions);
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

	public function test_undefined_markup()
	{
		$template = <<<EOT
<p:foreach>#{@}</p:foreach>
EOT;

		$engine = new Engine(new MarkupCollection, $this->functions);
		$rc = $engine($template);
		$this->assertContains('MarkupNotDefined', $rc);
	}

	public function test_function()
	{
		$template = <<<EOT
#{@hello()}
EOT;

		$engine = new Engine($this->markups, new FunctionCollection([

			'hello' => function($name = "world") {

				return "Hello $name!";

			}

		]));

		$rc = $engine($template, "Olivier");
		$this->assertEquals('Hello Olivier!', $rc);
	}

	public function test_undefined_function()
	{
		$template = <<<EOT
#{@undefined()}
EOT;

		$engine = new Engine($this->markups, new FunctionCollection);
		$rc = $engine($template, [ "1", "2", "3" ]);
		$this->assertContains('Unknown method', $rc);
	}

	/**
	 * @dataProvider provide_test_cases
	 *
	 * @param $base
	 */
	public function test_cases($base)
	{
		$thisArg = null;
		$options = [];

		require $base . '.php';

		$template = file_get_contents($base . '.patron');
		$expected = file_get_contents($base . '.expect');
		$engine = get_patron();
		$result = $engine($template, $thisArg, $options);

		$this->assertEquals($expected, $result);
	}

	public function provide_test_cases()
	{
		$di = new \DirectoryIterator(__DIR__ . '/cases');
		$di = new \RegexIterator($di, '/\.patron$/');

		$cases = [];

		foreach ($di as $file)
		{
			$cases[] = [ substr($file->getPathname(), 0, -strlen('.patron')) ];
		}

		return $cases;
	}
}
