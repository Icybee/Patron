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
 * An evaluation node.
 *
 * The node is evaluated with the engine's `evaluate()` method.
 */
class EvaluateNode extends ExpressionNode
{
	/**
	 * @var Engine
	 */
	private $engine;

	/**
	 * @var array
	 */
	private $engine_context;

	public function __invoke(Engine $engine, $context)
	{
		$this->engine = $engine;
		$this->engine_context = $context;

		return parent::__invoke($engine, $context);
	}

	protected function render($expression)
	{
		return $this->engine->evaluate($expression, false, $this->engine_context);
	}
}
