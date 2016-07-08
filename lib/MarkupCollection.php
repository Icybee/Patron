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
 * A markup collection
 */
class MarkupCollection implements \ArrayAccess
{
	/**
	 * @var array
	 */
	private $collection = [];

	/**
	 * @param array $markups
	 */
	public function __construct(array $markups = [])
	{
		foreach ($markups as $name => $definition)
		{
			$this[$name] = $definition;
		}
	}

	/**
	 * Whether a markup exists.
	 *
	 * @param string $name Markup name.
	 *
	 * @return boolean `true` if the markup exists, `false` otherwise.
	 */
	public function offsetExists($name)
	{
		return isset($this->collection[$name]);
	}

	/**
	 * Returns a markup definition.
	 *
	 * @param string $name Markup name.
	 *
	 * @return array The markup's definition.
	 *
	 * @throws MarkupNotDefined when the markup is not defined.
	 */
	public function offsetGet($name)
	{
		if (!$this->offsetExists($name))
		{
			throw new MarkupNotDefined([ $name, $this ]);
		}

		return $this->collection[$name];
	}

	/**
	 * Sets a markup definition.
	 *
	 * @param string $name Markup name.
	 * @param array $definition Markup definition.
	 */
	public function offsetSet($name, $definition)
	{
		$this->collection[$name] = $definition;
	}

	/**
	 * Removes a markup.
	 *
	 * @param string $name Markup name.
	 */
	public function offsetUnset($name)
	{
		unset($this->collection[$name]);
	}
}
