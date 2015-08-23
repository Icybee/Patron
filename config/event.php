<?php

namespace Patron;

use ICanBoogie;

$hooks = Hooks::class . '::';

return [

	ICanBoogie\Core::class . '::boot' => $hooks . 'on_core_boot'

];
