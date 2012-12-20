<?php

namespace Patron;

return array
(
	__NAMESPACE__ . '\Compiler' => $path . 'lib/patron/compiler.php',
	__NAMESPACE__ . '\ControlNode' => $path . 'lib/patron/nodes/control.php',
	__NAMESPACE__ . '\EvaluateNode' => $path . 'lib/patron/nodes/evaluate.php',
	__NAMESPACE__ . '\Template' => $path . 'lib/patron/template.php',

	__NAMESPACE__ . '\HTMLParser' => $path . 'lib/html-parser.php',
	__NAMESPACE__ . '\Engine' => $path . 'lib/patron/engine.php',
	__NAMESPACE__ . '\TextHole' => $path . 'lib/texthole.php',
	'Textmark_Parser' => $path . 'lib/textmark.php',

	__NAMESPACE__ . '\Hooks' => $path . 'lib/hooks.php'
);