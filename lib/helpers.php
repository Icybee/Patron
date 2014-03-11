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

function tr($str, $from, $to)
{
	return strtr($str, $from, $to);
}

/**
 * Initialize the parser and return the result of its publish method.
 *
 * @param $template
 * @return string The template published
 */
function render($template, $thisArg=null, array $options=array())
{
	static $engine;

	if (!$engine)
	{
		$engine = new Engine;
	}

// 	return Patron\Engine::get_singleton('Icybee')->__invoke($template, $bind, $options);
}


function by_columns(array $array, $columns, $pad=false)
{
	$values_by_columns = ceil(count($array) / $columns);

	$i = 0;
	$by_columns = array();

	foreach ($array as $value)
	{
		$by_columns[$i++ % $values_by_columns][] = $value;
	}

	return $by_columns;
}