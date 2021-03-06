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
 * Exception throw in attempt to obtain a function that is not defined.
 */
class FunctionNotDefined extends OffsetNotDefined implements Exception
{

}
