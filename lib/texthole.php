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

use ICanBoogie\Debug;
use ICanBoogie\Exception;
use ICanBoogie\Hook;

if (!defined('WDTEXTHOLE_USE_APC'))
{
	define('WDTEXTHOLE_USE_APC', function_exists('apc_store'));
}

class TextHole
{
	public function __construct()
	{
		$this->context = $this->contextInit();
	}

	protected $functions = array();

	public function addFunction($name, $callback)
	{
		#
		# FIXME-20080203: should check overrides
		#

		$this->functions[$name] = $callback;
	}

	public function findFunction($name)
	{
		/*
		// TODO: move to Engine

		$hook = null;

		try
		{
			$hook = Hook::find('patron.functions', $name);
		}
		catch (\Exception $e) { }

		if ($hook)
		{
			return $hook;
		}
		*/

		// /

		#
		#
		#

		if (isset($this->functions[$name]))
		{
			return $this->functions[$name];
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

		#
		# 'wd' pseudo namespace // COMPAT
		#

		$try = 'wd_' . str_replace('-', '_', $name);

		if (function_exists($try))
		{
			return $try;
		}

		$try = 'Patron\\' . $name;

		if (function_exists($try))
		{
			return $try;
		}
	}

	/*
	**

	CONTEXT

	**
	*/

	public $context = array();

	protected function contextInit()
	{
		return array
		(
			'$server' => &$_SERVER,
			'$request' => &$_REQUEST
		);
	}

	/*
	**

	SET & GET

	**
	*/

	#
	# support
	#

	static private function dotsToBrackets($string)
	{
		#
		# this function converts dot separated keys to PHP array notation e.g.
		# a.2.c => ['a'][2]['c']
		#

		$parts = explode('.', $string);

		$rc = NULL;

		foreach ($parts as $part)
		{
			$rc .= is_numeric($part) ? "[$part]" : "['$part']";
		}

		return $rc;
	}

	/*
	**

	PUBLISH

	**
	*/

	public function publish($template, $bind=null, array $options=array())
	{
		if ($bind !== null)
		{
			$this->context['this'] = $bind;
		}

		foreach ($options as $option => $value)
		{
			switch ((string) $option)
			{
				/*
				case 'bind':
				{
					$this->context['this'] = $options['bind'];
				}
				break;
				*/
				case 'variables':
				{
					$this->context = array_merge($this->context, $value);
				}
				break;

				default:
				{
					Debug::trigger('Suspicious option: %option :value', array('%option' => $option, ':value' => $value));
				}
				break;
			}
		}

		return $this->resolve($template);
	}

	protected function resolve($text)
	{
		$text = preg_replace_callback
		(
			'#\#\{(?!\s)([^\}]+)\}#', array($this, 'resolve_callback'), $text
		);

		return $text;
	}

	protected function resolve_callback($matches)
	{
		$expression = $matches[1];

		$rc = $this->evaluate($expression);

		if (is_object($rc))
		{
			if (!method_exists($rc, '__toString'))
			{
				$this->error('%expression was resolved to an object of the class %class', array('%expression' => $expression, '%class' => get_class($rc)));
			}

			$rc = (string) $rc;
		}
		else if (is_array($rc))
		{
			$this->error('%expression was resolved to an array with the following keys: :keys', array('%expression' => $expression, ':keys' => implode(', ', array_keys($rc))));
		}

		return $rc;
	}

	/*
	 * Decode Javascript style function chain into an array of identifiers and functions
	 */

	const TOKEN_TYPE = 'type';
	const TOKEN_TYPE_FUNCTION = 'function';
	const TOKEN_TYPE_IDENTIFIER = 'identifier';
	const TOKEN_VALUE = 'value';
	const TOKEN_ARGS = 'args';
	const TOKEN_ARGS_EVALUATE = 'args-evaluate';

	static protected function parseExpression_callback($str)
	{
		$str .= '.';

		$length = strlen($str);

		$quote = null;
		$quote_closed = null;
		$part = null;
		$escape = false;

		$function = null;
		$args = array();
		$args_evaluate = array();
		$args_count = 0;

		$parts = array();

		for ($i = 0 ; $i < $length ; $i++)
		{
			$c = $str{$i};

			if ($escape)
			{
				$part .= $c;

				$escape = false;

				continue;
			}

			if ($c == '\\')
			{
				//echo "found escape: [$c] @$i<br />";

				$escape = true;

				continue;
			}

			if ($c == '"' || $c == '\'' || $c == '`')
			{
				if ($quote && $quote == $c)
				{
					//echo "found closing quote: [$c]<br />";

					$quote = null;
					$quote_closed = $c;

					if ($function)
					{
						continue;
					}
				}
				else if (!$quote)
				{
					//echo "found opening quote: [$c]<br />";

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
				//echo "end of part: [$part]<br />";

				// FIXME: added strlen() because of '0' e.g. items.0.price
				// could a part be null ??

				if (strlen($part))
				{
					$parts[] = array
					(
						self::TOKEN_TYPE => self::TOKEN_TYPE_IDENTIFIER,
						self::TOKEN_VALUE => $part
					);
				}

				$part = null;

				continue;
			}

			if ($c == '(')
			{
				//echo "function [$part] begin: @$i<br />";

				$function = $part;

				$args = array();
				$args_count = 0;

				$part = null;

				continue;
			}

			if (($c == ',' || $c == ')') && $function)
			{
				//echo "function push argument [$part] q=[$quote_closed]<br />";

				if ($part !== null)
				{
					if ($quote_closed == '`')
					{
						//echo "we should evaluate [$part][$args_count]<br />";

						$args_evaluate[] = $args_count;
					}

					if (!$quote_closed)
					{
						#
						# end of an unquoted part.
						# it might be an integer, a float, or maybe a constant !
						#

						$part_back = $part;

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

						//\ICanBoogie\log('part: [\1] == [\2]', $part_back, $part);
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
				//echo "function end: [$part] @$i<br />";

				$parts[] = array
				(
					self::TOKEN_TYPE => self::TOKEN_TYPE_FUNCTION,
					self::TOKEN_VALUE => $function,
					self::TOKEN_ARGS => $args,
					self::TOKEN_ARGS_EVALUATE => $args_evaluate
				);

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

	protected static $function_chain_cache;
	protected static $function_chain_cache_usage;

	protected function parseExpression($expression)
	{
		$parsed = null;

		if (isset(self::$function_chain_cache[$expression]))
		{
			$parsed = self::$function_chain_cache[$expression];
		}

		if (!$parsed && WDTEXTHOLE_USE_APC)
		{
			$parsed = apc_fetch(__CLASS__ . '/' . $expression, $success);

			//\ICanBoogie\log("expression: <em>$expression</em> from APC");

			if (!$success)
			{
				$parsed = null;
			}

			self::$function_chain_cache[$expression] = $parsed;
		}

		if (!$parsed)
		{
			$parsed = self::parseExpression_callback($expression);

			self::$function_chain_cache[$expression] = $parsed;

			if (WDTEXTHOLE_USE_APC)
			{
				apc_store(__CLASS__ . '/' . $expression, $parsed);
			}
		}

		if (empty(self::$function_chain_cache_usage[$expression]))
		{
			self::$function_chain_cache_usage[$expression] = 0;
		}

		self::$function_chain_cache_usage[$expression]++;

		return $parsed;
	}

	public function evaluate($expression, $silent=false)
	{
		if (!$expression)
		{
			$this->error('Empty expression');

			return;
		}

		$value = $this->context;
		$previous_identifier = 'context';
		$work_expression = $expression;

		#
		# `@` (this) handling
		#

		if ($expression{0} == '@')
		{
			#
			# this is an instance variable, shortcut for "this."
			#

			if (!isset($this->context['this']))
			{
				$this->error
				(
					'Using <q>this</q> property when no <q>this</q> is defined: %identifier', array
					(
						'%identifier' => $expression
					)
				);

				return;
			}

			$value = $this->context['this'];
			$previous_identifier = 'this';
			$work_expression = substr($expression, 1);
		}
		else if ($expression{0} == '$')
		{
			$value = $GLOBALS;
			$previous_identifier = 'globals';
			$work_expression = substr($expression, 1);

			if (substr($work_expression, 0, 7) == 'request')
			{
				$value = $_REQUEST;
				$previous_identifier = 'request';
				$work_expression = substr($expression, 8);
			}
			else if (substr($work_expression, 0, 6) == 'server')
			{
				$value = $_SERVER;
				$previous_identifier = 'server';
				$work_expression = substr($expression, 7);
			}
			else if (substr($work_expression, 0, 6) == 'shared')
			{
				$value = $this->context_shared;
				$previous_identifier = 'shared';
				$work_expression = substr($expression, 7);
			}
		}

		#
		#
		#

		$parts = self::parseExpression($work_expression);

		foreach ($parts as $part)
		{
			$identifier = $part[self::TOKEN_VALUE];

			switch ($part[self::TOKEN_TYPE])
			{
				case self::TOKEN_TYPE_IDENTIFIER:
				{
					if (!is_array($value) && !is_object($value))
					{
						$this->error
						(
							'Unexpected variable type: %type (%value) for %identifier in expression %expression, should be either an array or an object', array
							(
								'%type' => gettype($value),
								'%value' => $value,
								'%identifier' => $identifier,
								'%expression' => $expression
							)
						);

						return;
					}

					$exists = false;

					if (is_array($value))
					{
						$exists = array_key_exists($identifier, $value);
					}
					else
					{
						$exists = property_exists($value, $identifier);

						if (!$exists && method_exists($value, 'has_property'))
						{
							$exists = $value->has_property($identifier);
						}
						else
						{
							if (!$exists && method_exists($value, 'offsetExists'))
							{
								$exists = $value->offsetExists($identifier);
							}

							if (!$exists && method_exists($value, '__get'))
							{
								$exists = true;
							}
						}
					}

					if (!$exists)
					{
						if (!$silent)
						{
							$this->error
							(
								'%identifier of expression %expression does not exists in %var (defined: :keys) in: !value', array
								(
									'%identifier' => $identifier,
									'%expression' => $expression,
									'%var' => $previous_identifier,
									':keys' => implode(', ', array_keys((array) $value)),
									'!value' => $value
								)
							);
						}

						return;
					}

					$value = (is_array($value) || method_exists($value, 'offsetExists')) ? $value[$identifier] : $value->$identifier;
					$previous_identifier = $identifier;
				}
				break;

				case self::TOKEN_TYPE_FUNCTION:
				{
					$method = $identifier;
					$args = $part[self::TOKEN_ARGS];
					$args_evaluate = $part[self::TOKEN_ARGS_EVALUATE];

					if ($args_evaluate)
					{
						$this->error('we should evaluate %eval', array('%eval' => $args_evaluate));
					}

					#
					# if value is an object, we check if the object has the method
					#

					if (is_object($value) && method_exists($value, $method))
					{
						$value = call_user_func_array(array($value, $method), $args);

						break;
					}

					#
					# well, the object didn't have the method,
					# we check internal functions
					#

					$callback = $this->findFunction($method);

					#
					# if no internal function matches, we try string and array functions
					# depending on the type of the value
					#

					if (!$callback)
					{
						if (is_string($value))
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
						else if (is_array($value) || is_object($value))
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
						if (is_object($value) && method_exists($value, '__call'))
						{
							$value = call_user_func_array(array($value, $method), $args);

							break;
						}
					}

					#
					#
					#

					if (!$callback)
					{
						$this->error
						(
							'Unknown method: %method for: %expression', array
							(
								'method' => $method,
								'expression' => $expression
							)
						);

						return;
					}

					#
					# create evaluation
					#

					array_unshift($args, $value);

					if (PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 2))
					{
						if ($callback == 'array_shift')
						{
							$value = array_shift($value);
						}
						else
						{
							$value = call_user_func_array($callback, $args);
						}
					}
					else
					{
						$value = call_user_func_array($callback, $args);
					}
				}
				break;
			}
		}

		return $value;
	}

	public function error($message, array $args=array())
	{
		Debug::trigger($message, $args);
	}
}