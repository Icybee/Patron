<?php

/*
 * This file is part of the Patron package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Patron\MarkupCollection;

use ICanBoogie\Event;
use Patron\MarkupCollection;

/**
 * Event class for the `Patron\MarkupCollection::alter` event.
 */
class AlterEvent extends Event
{
	const TYPE = 'alter';

	/**
	 * @param MarkupCollection $target
	 */
	public function __construct(MarkupCollection $target)
	{
		parent::__construct($target, self::TYPE);
	}
}
