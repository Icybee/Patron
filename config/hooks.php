<?php

namespace Patron;

$hooks = __NAMESPACE__ . '\Hooks::';

return [

	'events' => [

		'ICanBoogie\Core::boot' => $hooks . 'on_core_boot'

	]

];
