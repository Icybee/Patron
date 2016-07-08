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

/**
 * A function collection
 */
class FunctionCollection implements \ArrayAccess
{
	/**
	 * @var array
	 */
	private $collection = [];

	/**
	 * @param array $functions
	 */
	public function __construct(array $functions = [])
	{
		foreach ($functions as $name => $definition)
		{
			$this[$name] = $definition;
		}
	}

	/**
	 * Calls a function.
	 *
	 * @param string $method Function name.
	 * @param array $arguments Function arguments.
	 *
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
		$f = $this->find($method);

		if (!$f)
		{
			throw new FunctionNotDefined([ $method, $this ]);
		}

		return call_user_func_array($f, $arguments);
	}

	/**
	 * Whether a function exists.
	 *
	 * @param string $name Function name.
	 *
	 * @return boolean `true` if the function exists, `false` otherwise.
	 */
	public function offsetExists($name)
	{
		return isset($this->collection[$name]);
	}

	/**
	 * Returns a function definition.
	 *
	 * @param string $name Function name.
	 *
	 * @return array The function's definition.
	 *
	 * @throws FunctionNotDefined when the function is not defined.
	 */
	public function offsetGet($name)
	{
		if (!$this->offsetExists($name))
		{
			throw new FunctionNotDefined([ $name, $this ]);
		}

		return $this->collection[$name];
	}

	/**
	 * Sets a function definition.
	 *
	 * @param string $name Function name.
	 * @param array $definition Function definition.
	 */
	public function offsetSet($name, $definition)
	{
		$this->collection[$name] = $definition;
	}

	/**
	 * Removes a function.
	 *
	 * @param string $name Function name.
	 */
	public function offsetUnset($name)
	{
		unset($this->collection[$name]);
	}

	/**
	 * Finds a function.
	 *
	 * @param string $name Function name.
	 *
	 * @return callable|false A callable is the function is defined, `false` otherwise.
	 */
	public function find($name)
	{
		if (isset($this->collection[$name]))
		{
			return $this->collection[$name];
		}

		$try = 'ICanBoogie\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}

		$try = 'ICanBoogie\I18n\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}

		$try = 'Patron\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}

		return false;
	}
}
