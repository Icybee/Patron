<?php

namespace Patron;

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
