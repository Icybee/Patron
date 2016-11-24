<?php

namespace Patron;

use ICanBoogie;

$hooks = Hooks::class . '::';

return [

	ICanBoogie\Application::class . '::boot' => $hooks . 'on_core_boot'

];
