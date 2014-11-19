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

class Hook
{
	const CONFIG_KEY = 'patron.markups';

	static protected $hooks = [];

	static public function config_constructor(array $fragments)
	{
		$markups = [];

		foreach ($fragments as $path => $fragment)
		{
			if (empty($fragment[self::CONFIG_KEY]))
			{
				continue;
			}

			$markups = array_merge($markups, $fragment[self::CONFIG_KEY]);
		}

		return $markups;
	}

	static public function add($name, array $definition)
	{
		self::$hooks[$name] = $definition;
	}

	static public function find($name)
	{
		if (!self::$hooks)
		{
			self::$hooks = \ICanBoogie\app()->configs->synthesize('hooks', __CLASS__ . '::config_constructor');
		}

		if (empty(self::$hooks[$name]))
		{
			throw new \Exception(\ICanBoogie\format('Undefined hook %name', [ '%name' => $name ]));
		}

		$hook = self::$hooks[$name];

		#
		# `$hook` is an array when the hook has not been created yet, in which case we create the
		# hook on the fly.
		#

		if (!($hook instanceof Hook))
		{
			$tags = $hook;

			list($callback, $params) = $tags + [ 1 => [] ];

			unset($tags[0]);
			unset($tags[1]);

			self::$hooks[$name] = $hook = new Hook($callback, $params, $tags);
		}

		return $hook;
	}

	public $callback;
	public $params = [];
	public $tags = [];

	public function __construct($callback, array $params=[], array $tags=[])
	{
		$this->callback = $callback;
		$this->params = $params;
		$this->tags = $tags;
	}

	public function __invoke()
	{
		$args = func_get_args();

		return call_user_func_array($this->callback, $args);
	}
}
