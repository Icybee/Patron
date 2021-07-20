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

class ControlNode extends Node
{
	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var array
	 */
	public $args;

	/**
	 * @var Node[]
	 */
	public $nodes;

	/**
	 * @param string $name
	 * @param array $args
	 * @param Node[] $nodes
	 */
	public function __construct($name, array $args, array $nodes)
	{
		$this->name = $name;

		foreach ($nodes as $i => $node)
		{
			if (!($node instanceof self) || $node->name != 'with-param')
			{
				continue;
			}

			$args[$node->args['name']] = $node;

			unset($nodes[$i]);
		}

		$this->args = $args;
		$this->nodes = $nodes;
	}

	public function __invoke(Engine $engine, $context)
	{
		$name = $this->name;

		list($callback, $params) = $engine->markups[$name];

		$args = $this->args;

		$missing = [];
		$binding = empty($params['no-binding']);

		foreach ($params as $param => $options)
		{
			if (is_array($options))
			{
				#
				# default value
				#

				if (isset($options['default']) && !array_key_exists($param, $args))
				{
					$args[$param] = $options['default'];
				}

				if (array_key_exists($param, $args))
				{
					$value = $args[$param];

					if (isset($options['expression']))
					{
						$silent = !empty($options['expression']['silent']);

						//\ICanBoogie\log('\4:: evaluate expression "\3" with value: \5, params \1 and args \2', array($hook->params, $args, $param, $name, $value));

						if ($value[0] == ':')
						{
							$args[$param] = substr($value, 1);
						}
						else
						{
							$args[$param] = $engine->evaluate($value, $silent, $context);
						}
					}
				}
				else if (isset($options['required']))
				{
					$missing[$param] = true;
				}
			}
			else
			{
				if (!array_key_exists($param, $args))
				{
					$args[$param] = $options;
				}
			}

			if (!isset($args[$param]))
			{
				$args[$param] = null;
			}
		}

		if ($missing)
		{
			throw new \Exception(\ICanBoogie\format('The %param parameter is required for the %markup markup, given %args', [

				'%param' => implode(', ', array_keys($missing)),
				'%markup' => $name,
				'%args' => json_encode($args)

			]));
		}

		#
		# resolve arguments
		#

		foreach ($args as &$arg)
		{
			if ($arg instanceof ControlNode)
			{
				if (isset($arg->args['select']))
				{
					$arg = $engine->evaluate($arg->args['select'], false, $context);
				}
				else
				{
					$arg = $engine($arg->nodes);
				}
			}
		}

		unset($arg);

		#
		# call hook
		#

		$engine->trace_enter([ 'markup', $name ]);

		if ($binding)
		{
			array_push($engine->context_markup, [ $engine->context['self'], $engine->context['this'] ]);

			$engine->context['self'] = [

				'name' => $name,
				'arguments' => $args

			];
		}

		$rc = null;

		try
		{
			$rc = call_user_func($callback, $args, $engine, $this->nodes);
		}
		catch (\Exception $e)
		{
			$engine->handle_exception($e);
		}

		if ($binding)
		{
			$a = array_pop($engine->context_markup);

			$engine->context['self'] = $a[0];
			$engine->context['this'] = $a[1];
		}

		$engine->trace_exit();

		return $rc;
	}
}
