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
 * @return MarkupCollection
 */
function get_markups()
{
	static $markups;

	if (!$markups)
	{
		$markups = new MarkupCollection(require __DIR__ . '/../res' . DIRECTORY_SEPARATOR . 'markups.php');

		new MarkupCollection\AlterEvent($markups);
	}

	return $markups;
}

/**
 * @return FunctionCollection
 */
function get_functions()
{
	static $functions;

	if (!$functions)
	{
		$functions = new FunctionCollection(require __DIR__ . '/../res' . DIRECTORY_SEPARATOR . 'functions.php');

		new FunctionCollection\AlterEvent($functions);
	}

	return $functions;
}

/**
 * @return Engine
 */
function get_patron()
{
	static $patron;

	if (!$patron)
	{
		$patron = new Engine(get_markups(), get_functions());
	}

	return clone $patron;
}

/*
 *
 */

function tr($str, $from, $to)
{
	return strtr($str, $from, $to);
}

function by_columns(array $array, $columns)
{
	$values_by_columns = ceil(count($array) / $columns);

	$i = 0;
	$by_columns = [];

	foreach ($array as $value)
	{
		$by_columns[$i++ % $values_by_columns][] = $value;
	}

	return $by_columns;
}
