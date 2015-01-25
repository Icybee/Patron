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

use ICanBoogie\Render;

/**
 * Exception thrown when a template specified by its name could not be found.
 */
class TemplateNotFound extends Render\TemplateNotFound
{

}
