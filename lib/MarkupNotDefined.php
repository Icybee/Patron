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

use ICanBoogie\OffsetNotDefined;

/**
 * Exception thrown in attempt to obtain a markup that is not defined.
 */
class MarkupNotDefined extends OffsetNotDefined implements Exception
{

}
