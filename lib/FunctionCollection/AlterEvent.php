<?php

/*
 * This file is part of the Patron package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Patron\FunctionCollection;

use ICanBoogie\Event;
use Patron\FunctionCollection;

/**
 * Event class for the `Patron\FunctionCollection::alter` event.
 */
class AlterEvent extends Event
{
	const TYPE = 'alter';

	/**
	 * @param FunctionCollection $target
	 */
	public function __construct(FunctionCollection $target)
	{
		parent::__construct($target, self::TYPE);
	}
}
