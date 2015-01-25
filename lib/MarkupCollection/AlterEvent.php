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
 *
 * @package Patron\MarkupCollection
 */
class AlterEvent extends Event
{
	public function __construct(MarkupCollection $target)
	{
		parent::__construct($target, 'alter');
	}
}
