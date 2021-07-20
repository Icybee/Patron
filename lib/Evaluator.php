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

use BlueTihi\Context;

/**
 * Evaluate expression relative to a context.
 */
class Evaluator
{
	const TOKEN_TYPE = 1;
	const TOKEN_TYPE_FUNCTION = 2;
	const TOKEN_TYPE_IDENTIFIER = 3;
	const TOKEN_VALUE = 4;
	const TOKEN_ARGS = 5;
	const TOKEN_ARGS_EVALUATE = 6;

	/**
	 * @var Engine
	 */
	private $engine;

	/**
	 * @param Engine $engine
	 */
	public function __construct(Engine $engine)
	{
		$this->engine = $engine;
	}

	/**
	 * Evaluate an expression relative to a context.
	 *
	 * @param mixed $context
	 * @param string $expression
	 * @param bool $silent `true` to suppress errors, `false` otherwise.
	 *
	 * @return mixed
	 */
	public function __invoke($context, $expression, $silent = false)
	{
		$tokens = $this->tokenize($expression);

		return $this->evaluate($context, $expression, $tokens, $silent);
	}

	/**
	 * Tokenize Javascript style function chain into an array of identifiers and functions
	 *
	 * @param string $str
	 *
	 * @return array
	 */
	protected function tokenize($str)
	{
		if ($str[0] == '@')
		{
			$str = 'this.' . substr($str, 1);
		}

		$str .= '.';

		$length = strlen($str);

		$quote = null;
		$quote_closed = null;
		$part = null;
		$escape = false;

		$function = null;
		$args = [];
		$args_evaluate = [];
		$args_count = 0;

		$parts = [];

		for ($i = 0 ; $i < $length ; $i++)
		{
			$c = $str[$i];

			if ($escape)
			{
				$part .= $c;

				$escape = false;

				continue;
			}

			if ($c == '\\')
			{
				$escape = true;

				continue;
			}

			if ($c == '"' || $c == '\'' || $c == '`')
			{
				if ($quote && $quote == $c)
				{
					$quote = null;
					$quote_closed = $c;

					if ($function)
					{
						continue;
					}
				}
				else if (!$quote)
				{
					$quote = $c;

					if ($function)
					{
						continue;
					}
				}
			}

			if ($quote)
			{
				$part .= $c;

				continue;
			}

			#
			# we are not in a quote
			#

			if ($c == '.')
			{
				if (strlen($part))
				{
					$parts[] = [

						self::TOKEN_TYPE => self::TOKEN_TYPE_IDENTIFIER,
						self::TOKEN_VALUE => $part

					];
				}

				$part = null;

				continue;
			}

			if ($c == '(')
			{
				$function = $part;

				$args = [];
				$args_count = 0;

				$part = null;

				continue;
			}

			if (($c == ',' || $c == ')') && $function)
			{
				if ($part !== null)
				{
					if ($quote_closed == '`')
					{
						$args_evaluate[] = $args_count;
					}

					if (!$quote_closed)
					{
						#
						# end of an unquoted part.
						# it might be an integer, a float, or maybe a constant !
						#

						switch ($part)
						{
							case 'true':
							case 'TRUE':
							{
								$part = true;
							}
							break;

							case 'false':
							case 'FALSE':
							{
								$part = false;
							}
							break;

							case 'null':
							case 'NULL':
							{
								$part = null;
							}
							break;

							default:
							{
								if (is_numeric($part))
								{
									$part = (int) $part;
								}
								else if (is_float($part))
								{
									$part = (float) $part;
								}
								else
								{
									$part = constant($part);
								}
							}
							break;
						}
					}

					$args[] = $part;
					$args_count++;

					$part = null;
				}

				$quote_closed = null;

				if ($c != ')')
				{
					continue;
				}
			}

			if ($c == ')' && $function)
			{
				$parts[] = [

					self::TOKEN_TYPE => self::TOKEN_TYPE_FUNCTION,
					self::TOKEN_VALUE => $function,
					self::TOKEN_ARGS => $args,
					self::TOKEN_ARGS_EVALUATE => $args_evaluate

				];

				continue;
			}

			if ($c == ' ' && $function)
			{
				continue;
			}

			$part .= $c;
		}

		return $parts;
	}

	protected function evaluate($context, $expression, $tokens, $silent)
	{
		$expression_path = [];

		foreach ($tokens as $part)
		{
			$identifier = $part[self::TOKEN_VALUE];

			$expression_path[] = $identifier;

			switch ($part[self::TOKEN_TYPE])
			{
				case self::TOKEN_TYPE_IDENTIFIER:
				{
					if (!is_array($context) && !is_object($context))
					{
						throw new \InvalidArgumentException(\ICanBoogie\format
						(
							'Unexpected variable type: %type (%value) for %identifier in expression %expression, should be either an array or an object', [

								'%type' => gettype($context),
								'%value' => $context,
								'%identifier' => $identifier,
								'%expression' => $expression

							]
						));
					}

					$exists = false;
					$next_value = $this->extract_value($context, $identifier, $exists);

					if (!$exists)
					{
						if ($silent)
						{
							return null;
						}

						throw new ReferenceError(\ICanBoogie\format('Reference to undefined property %path of expression %expression (defined: :keys) in: :value', [

							'path' => implode('.', $expression_path),
							'expression' => $expression,
							'keys' => implode(', ', $context instanceof Context ? $context->keys() : array_keys((array) $context)),
							'value' => \ICanBoogie\dump($context)

						]));
					}

					$context = $next_value;
				}
				break;

				case self::TOKEN_TYPE_FUNCTION:
				{
					$method = $identifier;
					$args = $part[self::TOKEN_ARGS];
					$args_evaluate = $part[self::TOKEN_ARGS_EVALUATE];

					if ($args_evaluate)
					{
						$this->engine->error('we should evaluate %eval', [ '%eval' => $args_evaluate ]);
					}

					#
					# if value is an object, we check if the object has the method
					#

					if (is_object($context) && method_exists($context, $method))
					{
						$context = call_user_func_array([ $context, $method ], $args);

						break;
					}

					#
					# well, the object didn't have the method,
					# we check internal functions
					#

					$callback = $this->engine->functions->find($method);

					#
					# if no internal function matches, we try string and array functions
					# depending on the type of the value
					#

					if (!$callback)
					{
						if (is_string($context))
						{
							if (function_exists('str' . $method))
							{
								$callback = 'str' . $method;
							}
							else if (function_exists('str_' . $method))
							{
								$callback = 'str_' . $method;
							}
						}
						else if (is_array($context) || is_object($context))
						{
							if (function_exists('ICanBoogie\array_' . $method))
							{
								$callback = 'ICanBoogie\array_' . $method;
							}
							else if (function_exists('array_' . $method))
							{
								$callback = 'array_' . $method;
							}
						}
					}

					#
					# our last hope is to try the function "as is"
					#

					if (!$callback)
					{
						if (function_exists($method))
						{
							$callback = $method;
						}
					}

					if (!$callback)
					{
						if (is_object($context) && method_exists($context, '__call'))
						{
							$context = call_user_func_array([ $context, $method ], $args);

							break;
						}
					}

					#
					#
					#

					if (!$callback)
					{
						throw new \Exception(\ICanBoogie\format('Unknown method %method for expression %expression.', [

							'%method' => $method,
							'%expression' => $expression

						]));
					}

					#
					# create evaluation
					#

					array_unshift($args, $context);

					if (PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2))
					{
						if ($callback == 'array_shift')
						{
							$context = array_shift($context);
						}
						else
						{
							$context = call_user_func_array($callback, $args);
						}
					}
					else
					{
						$context = call_user_func_array($callback, $args);
					}
				}
				break;
			}
		}

		return $context;
	}

	/**
	 * Extract a value from a container.
	 *
	 * @param mixed $container A value can be extracted from the following containers, in that
	 * order:
	 *
	 * - An array, where the `$identifier` key exists.
	 * - An object implementing the `$identifier` property.
	 * - An object implementing `has_property()` which is used to determine if the object
	 * implements the property.
	 * - An object implementing `ArrayAccess`, where the `$identifier` offset exists.
	 * - Finaly, an object implementing `__get()`.
	 *
	 * @param string $identifier The identifier of the value to extract.
	 * @param bool $exists `true` when the value was extracted, `false` otherwise.
	 *
	 * @return mixed The extracted value.
	 */
	protected function extract_value($container, $identifier, &$exists=false)
	{
		$exists = false;

		# array

		if (is_array($container))
		{
			$exists = array_key_exists($identifier, $container);

			return $exists ? $container[$identifier] : null;
		}

		# object

		$exists = property_exists($container, $identifier);

		if ($exists)
		{
			return $container->$identifier;
		}

		if (method_exists($container, 'has_property'))
		{
			$exists = $container->has_property($identifier);

			return $exists ? $container->$identifier : null;
		}

		if ($container instanceof \ArrayAccess)
		{
			$exists = $container->offsetExists($identifier);

			if ($exists)
			{
				return $container[$identifier];
			}
		}

		if (method_exists($container, '__get'))
		{
			$exists = true;

			return $container->$identifier;
		}

		return null;
	}
}
