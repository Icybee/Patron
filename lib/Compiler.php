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

use ICanBoogie\I18n;

class Compiler
{
	public function __invoke($template)
	{
		$parser = new HTMLParser([

			HTMLParser::T_ERROR_HANDLER => function($message, array $args) {

				throw new \Exception(\ICanBoogie\format($message, $args));
			}

		]);

		$tree = $parser->parse($template, Engine::PREFIX);

		return $this->parse_html_tree($tree);
	}

	protected function parse_html_tree(array $tree)
	{
		$nodes = [];

		foreach ($tree as $node)
		{
			if (is_array($node))
			{
				$children = [];

				if (isset($node['children']))
				{
					$children = $this->parse_html_tree($node['children']);
				}

				$nodes[] = new ControlNode($node['name'], $node['args'], $children);
			}
			else
			{
				#
				# we don't resolve comments, unless they are Internet Explorer comments e.g. <!--[
				#

				$parts = preg_split('#(<!--(?!\[).+-->)#sU', $node, -1, PREG_SPLIT_DELIM_CAPTURE);

				if (count($parts) == 1)
				{
					$children = $this->parse_html_node($node);

					$nodes = array_merge($nodes, $children);
				}
				else
				{
					#
					# The comments, which are on odd position, are kept intact. The text, which is
					# on even position is resolved.
					#

					foreach ($parts as $i => $part)
					{
						if ($i % 2)
						{
							$nodes[] = new TextNode($part);
						}
						else
						{
							$children = $this->parse_html_node($part);

							$nodes = array_merge($nodes, $children);
						}
					}
				}
			}
		}

		return $nodes;
	}

	protected function parse_html_node($node)
	{
		$nodes = [];
		$parts = preg_split(ExpressionNode::REGEX, $node, -1, PREG_SPLIT_DELIM_CAPTURE);

		foreach ($parts as $i => $part)
		{
			if ($i % 2)
			{
				$nodes[] = $this->parse_expression($part);
			}
			else
			{
				$nodes[] = new TextNode($part);
			}
		}

		return $nodes;
	}

	protected function parse_expression($source)
	{
		$escape = true;

		if ($source{strlen($source) - 1} == '=')
		{
			$escape = false;
			$source = substr($source, 0, -1);
		}

		preg_match('/^(([a-z]+):)?(.+)$/', $source, $matches);

		$type = $matches[2];
		$expression = $matches[3];

		$types = [

			'' => 'Patron\EvaluateNode',
			't' => 'Patron\TranslateNode',
			'url' => 'Patron\URLNode'

		];

		if (!isset($types[$type]))
		{
			throw new \Exception(\ICanBoogie\format("Unknown expression type %type for expression %expression", [

				'type' => $type,
				'expression' => $expression

			]));
		}

		$class = $types[$type];

		return new $class($expression, $escape);
	}
}

abstract class Node
{
	abstract public function __invoke(Engine $engine, $context);
}

class TextNode extends Node
{
	protected $text;

	public function __construct($source)
	{
		$this->text = $source;
	}

	public function __invoke(Engine $engine, $context)
	{
		return $this->text;
	}
}

class ExpressionNode extends Node
{
	const REGEX = '~\#\{(?!\s)([^\}]+)\}~';

	protected $expression;
	protected $escape;

	public function __construct($expression, $escape)
	{
		$this->expression = $expression;
		$this->escape = $escape;
	}

	public function __invoke(Engine $engine, $context)
	{
		$rc = $this->render($this->expression);

		if ($this->escape)
		{
			$rc = \ICanBoogie\escape($rc);
		}

		return $rc;
	}

	protected function render($expression)
	{
		return $expression;
	}
}

class TranslateNode extends ExpressionNode
{
	protected function render($expression)
	{
		return I18n\t($expression);
	}
}

class URLNode extends ExpressionNode
{
	protected function render($expression)
	{
		$app = \ICanBoogie\app();

		if (isset($app->routes[$expression]))
		{
			$route = $app->routes[$expression];

			return $route->url;
		}

		return $app->site->resolve_view_url($expression);
	}
}
