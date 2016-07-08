<?php

namespace Patron;

class TextNode extends Node
{
	/**
	 * @var string
	 */
	private $text;

	/**
	 * @param $text
	 */
	public function __construct($text)
	{
		$this->text = $text;
	}

	/**
	 * @inheritdoc
	 */
	public function __invoke(Engine $engine, $context)
	{
		return $this->text;
	}
}
