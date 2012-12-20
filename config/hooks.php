<?php

namespace Patron;

$hooks = __NAMESPACE__ . '\Hooks::';

return array
(
	'patron.markups' => array
	(
		/*
		 * controls
		 */

		'template' => array
		(
			$hooks . 'markup_template', array
			(
				'name' => array('required' => true)
			),

			'no-binding' => true
		),

		'call-template' => array
		(
			$hooks . 'markup_call_template', array
			(
				'name' => array('required' => true)
			),

			'no-binding' => true
		),

		'foreach' => array
		(
			$hooks . 'markup_foreach', array
			(
				'in' => array('default' => 'this', 'expression' => true),
				'as' => null
			)
		),

		'variable' => array
		(
			$hooks . 'markup_variable', array
			(
				'name' => array('required' => true),
				'select' => array('expression' => true)
			),

			'no-binding' => true
		),

		'with' => array
		(
			$hooks . 'markup_with', array
			(
				'select' => array('expression' => true)
			)
		),

		'choose' => array
		(
			$hooks . 'markup_choose', array
			(

			),

			'no-binding' => true
		),

		'if' => array
		(
			$hooks . 'markup_if', array
			(
				'test' => array('expression' => array('silent' => true)),
				'select' => array('expression' => array('silent' => true)),
				'equals' => null
			),

			'no-binding' => true
		),

		/*
		 * elements
		 */

		'pager' => array
		(
			$hooks . 'markup_pager', array
			(
				'count' => null,
				'page' => null,
				'limit' => null,
				'with' => null,
				'range' => array('expression' => true),
				'noarrows' => false
			)
		)
	)
);