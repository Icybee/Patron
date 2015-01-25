<?php

namespace Patron;

$hooks = __NAMESPACE__ . '\Hooks::';

return [

	/*
	 * controls
	 */

	'template' => [

		$hooks . 'markup_template', [

			'name' => [ 'required' => true ]

		],

		'no-binding' => true

	],

	'call-template' => [

		$hooks . 'markup_call_template', [

			'name' => [ 'required' => true ]

		],

		'no-binding' => true

	],

	'decorate' => [

		$hooks . 'markup_decorate', [

			'with' => [ 'required' => true ]

		]
	],

	'foreach' => [

		$hooks . 'markup_foreach', [

			'in' => [ 'default' => 'this', 'expression' => true ],
			'as' => null

		]
	],

	'variable' => [

		$hooks . 'markup_variable', [

			'name' => [ 'required' => true ],
			'select' => [ 'expression' => true ]

		],

		'no-binding' => true
	],

	'with' => [

		$hooks . 'markup_with', [

			'select' => [ 'expression' => true ]

		]
	],

	'choose' => [

		$hooks . 'markup_choose', [


		],

		'no-binding' => true

	],

	'if' => [

		$hooks . 'markup_if', [

			'test' => [ 'expression' => [ 'silent' => true ] ],
			'select' => [ 'expression' => [ 'silent' => true ] ],
			'equals' => null

		],

		'no-binding' => true

	],

	'translate' => [

		$hooks . 'markup_translate', [

			'native' => [ 'required' => true ]

		]
	],

	/*
	 * Brickrouge
	 */

	'pager' => [

		$hooks . 'markup_pager', [

			'count' => null,
			'page' => null,
			'limit' => null,
			'with' => null,
			'range' => [ 'expression' => true ],
			'noarrows' => false

		]
	],

	'document:css' => [

		$hooks . 'markup_document_css', [

			'href' => null,
			'weight' => 100

		]
	],

	'document:js' => [

		$hooks . 'markup_document_js', [

			'href' => null,
			'weight' => 100

		]
	]

];
