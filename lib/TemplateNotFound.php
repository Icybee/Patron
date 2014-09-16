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
 * Exception thrown when a template specified by its name could not be found.
 */
class TemplateNotFound extends \InvalidArgumentException
{
	use \ICanBoogie\PrototypeTrait;

	private $template_name;
	private $tries;

	public function __construct($template_name, array $tries, $code=500, \Exception $previous=null)
	{
		$this->template_name = $template_name;
		$this->tries = $tries;

		parent::__construct("Template not found: $template_name", $code, $previous);
	}

	protected function get_template_name()
	{
		return $this->template_name;
	}

	protected function get_tries()
	{
		return $this->tries;
	}
}